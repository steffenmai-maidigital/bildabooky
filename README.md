# 📚 BilDaBooky — Community Edition

**Freie, selbst gehostete Buch-Werkstatt für Schüler.**
Die *Community Edition* ist die vollständige Open-Source-Fassung (AGPL-3.0).
Kinder gestalten eigene Bücher mit Text, Fotos, Zeichnungen, Tonaufnahmen und
Stickern – auf dem Tablet, am Laptop oder am Handy. Alle Daten bleiben auf dem
Server der Schule. Es gibt keine Cloud, kein Tracking, keine fremden Schriften
und keine Werbung.

*English version: [README.en.md](README.en.md)*

---

## Warum?

Viele Eltern möchten nicht, dass Fotos, Stimmen und Texte ihrer Kinder bei
einem Anbieter im Ausland liegen. BilDaBooky läuft deshalb komplett auf
eurem eigenen Server (z. B. einer Synology im Schulnetz):

- **keine externen Dienste**: keine CDNs, Google-Fonts oder Analyse-Skripte.
  Der Browser lädt nur Dateien vom Schulserver.
- **Fotos werden im Browser verkleinert**. Dabei fallen auch die Standortdaten
  (EXIF/GPS) weg, bevor etwas hochgeladen wird.
- **Medien sind nicht öffentlich**: Bilder und Töne liefert der Server nur an
  angemeldete Personen aus, die das Buch sehen dürfen.
- **Passwörter** liegen nur als bcrypt-Hash vor. Optional gibt es
  Zwei-Faktor-Anmeldung per Authenticator-App (TOTP). Lehrkräfte und Admins
  können sich auf Wunsch mit ihrem **Schulkonto** anmelden (Active Directory,
  Samba, Univention, OpenLDAP) — Kinder haben immer eigene Konten.
- **Bücher zum Mitnehmen**: Jedes Buch lässt sich als *eine* HTML-Datei
  herunterladen (mit allen Bildern und Tönen). Sie läuft offline in jedem
  Browser, auch nach dem Schuljahr.

## Funktionen

| Bereich | Was geht |
|---|---|
| Gestalten | Text (5 Schriften, Farben, Größe, Hinterlegung), Fotos, Kamera, Malen mit Radierer, Tonaufnahme, rund 900 Sticker in zehn Gruppen (überall gleich aussehend), Formen, Seitenfarben |
| Bedienen | Ziehen, Größe ändern, Drehen (mit Einrasten), Ebenen, Doppeln, Rückgängig, automatisches Speichern, große Knöpfe für Kinderhände |
| Formate | Hochformat, Quadrat, Querformat |
| Lesen | Blättern per Knopf, Wischen oder Pfeiltasten, Vollbild, Töne antippen |
| Teilen | zwei Knöpfe am eigenen Buch: **mit der Lehrkraft** (erscheint bei ihr unter „Bücher der Kinder“ in der Gruppe „Geteilt“) oder **mit der Klasse** (alle Kinder können es lesen) |
| Fuchs-Helfer | Rua, der Fuchs (ein Wikinger-Fuchs aus einem norwegischen Fjorddorf, den die Iren „den Roten“ nannten), begrüßt animiert unten rechts mit Namen, lobt nach dem Speichern und gibt Tipps passend zu Rolle und Seite; im Editor reagiert er auf das Geschehen, beim Lesen begleitet er das Blättern; von sich aus spricht er höchstens alle 3 Minuten; „Schlafen legen“ lässt ihn schlafend liegen, bis man ihn antippt; auf der Anmeldeseite wirbt er für freie Software (mit Link auf `SOURCE_URL`); respektiert „weniger Bewegung“. Admins schalten ihn zentral aus, jede Person im eigenen Konto |
| Sommerferienmodus | Admins setzen ein Datum; an dem Tag um 3 Uhr werden alle Schülerkonten mit Büchern, Fotos, Tonaufnahmen und Protokolleinträgen endgültig gelöscht — mit Sicherung vorher und Hinweis an die Kinder drei Wochen vorher |
| Klassenbuch | die ganze Klasse schreibt gemeinsam in einem Buch; Änderungen der anderen erscheinen nach wenigen Sekunden, gleichzeitige Änderungen an derselben Seite werden zusammengeführt, man sieht, wer gerade wo arbeitet |
| Klassen und Fächer | Admins legen Klassen und Fächer an und stellen je Klasse ein, welche Fächer es gibt und wer sie unterrichtet; eine Klasse kann mehrere Lehrkräfte haben, eine Lehrkraft mehrere Klassen mit unterschiedlichen Fächern. Kinder sehen ihre Bücher nach Fächern geordnet, legen sie im Fach an und verschieben sie; alle Lehrkräfte einer Klasse sehen alle Bücher der Klasse, mit Fach-Filter (eigene Fächer zuerst) |
| Vorlagen | die Lehrkraft gibt ein Buch vor, jedes Kind legt seine eigene Version an; Übersicht „Versionen der Kinder“ mit „angefangen“ und „abgegeben“ |
| Änderungsprotokoll | je Buch: wer wann was geändert hat (Text, Bild, Seiten, Titel, Teilen, Abgabe) — nur für Lehrkräfte und Admins |
| Ausgabe | Drucken / PDF (A4 bzw. quadratisch), Datei zum Mitnehmen |
| Benutzer | Vor- und Nachname, Benutzername, Klasse; ganze Klassenlisten auf einmal anlegen |
| Anmeldekarten | optional: Passwort zusätzlich verschlüsselt hinterlegen, damit Karten jederzeit neu gedruckt werden können |
| Schulverzeichnis | optional unter **Einstellungen → 9**: Lehrkräfte und Admins melden sich mit ihrem Schulkonto an (LDAP-Bind, kein Passwort in BilDaBooky); Rolle aus Gruppen (Lehrkräfte, Admins), Konto entsteht beim ersten Anmelden, „Verbindung testen“; eigene Konten funktionieren weiterhin |
| Anmelde-Sitzungen | Kinder melden sich nur an, wenn eine Lehrkraft ihrer Klasse die Anmeldung freigeschaltet hat (60/120/180 Minuten oder bis zu 7 Tage, dann bis 17 Uhr), mit Passwort **und** 6-stelligem Sitzungscode — das ersetzt bei Kindern die Zwei-Faktor-Anmeldung. Alle Lehrkräfte der Klasse sehen Grund, Code und wer angemeldet ist und können die Sitzung mit Grund vorzeitig beenden (Kinder werden abgemeldet). Verlauf 60 Tage. Vom Admin abschaltbar |
| Sicherheit | Zwei-Faktor (TOTP) mit Wiederherstellungscodes für Lehrkräfte und Admins — je Gruppe Pflicht, wählbar oder aus, Gruppen des Schulverzeichnisses mit Pflicht, Ausnahme je Person; Bremse gegen Passwort- und Code-Raten |
| Sprachen | Deutsch, Englisch, Französisch, Spanisch. Die Sprache wird am Browser erkannt und lässt sich umschalten. Was fehlt, erscheint auf Deutsch. |
| Gestaltung | Admins stellen Schulname, Logo, Farben (Akzent, Kopfzeile, Hintergrund), Fußzeilentext und Domain direkt in der Oberfläche ein |
| Impressum und Datenschutz | einzeln einschaltbar, als eigener Text oder Link auf die Schulhomepage; ohne Anmeldung erreichbar, verlinkt auf der Anmeldeseite und in der Fußzeile; mit Vorlage für die Datenschutzerklärung |
| Hilfe | Kurzanleitung, System, Lizenz, letzte Fehler der vergangenen 30 Tage (Admins, mit „Protokoll leeren“) und Changelog unter „Hilfe“ in der Fußzeile |
| Betrieb | Docker, SQLite, nächtliche Sicherung (Datenbank + Medien als ZIP) |

## Rollen

| Rolle | Darf |
|---|---|
| **Admin** | ein Haken bei einer Lehrkraft („Zusätzlich Admin“) — Verwaltung: Klassen und Fächer anlegen, Lehrkräfte und Admins verwalten, Gestaltung, Domain und Lizenz, Sicherung herunterladen. **Keine** Bücher anderer — außer in Klassen, in denen der Admin selbst unterrichtet (dann wie eine Lehrkraft) |
| **Leiter** (Lehrkraft) | leitet eine oder mehrere Klassen: legt dort Schüler an, setzt Passwörter zurück, druckt Anmeldekarten; legt Klassenbücher und Vorlagen an; sieht und bearbeitet **alle** Bücher ihrer Klasse; liest das Änderungsprotokoll |
| **Ersteller** (Schüler) | eigene Bücher gestalten, teilen und mitnehmen; in Klassenbüchern mitschreiben; aus Vorlagen eine eigene Version anlegen und abgeben; geteilte Bücher der Klasse lesen |

### Wer sieht welches Buch?

| Buch | Kind (Besitzer) | andere Kinder der Klasse | Lehrkraft der Klasse |
|---|---|---|---|
| eigenes Buch, nicht geteilt | bearbeiten | — | sehen, helfen, Protokoll |
| eigenes Buch, „mit der Lehrkraft“ | bearbeiten | — | wie oben, beim Kind in der Gruppe „Geteilt“ |
| eigenes Buch, „mit der Klasse“ | bearbeiten | lesen | wie oben, beim Kind in der Gruppe „Geteilt“ |
| Klassenbuch | — | alle bearbeiten | bearbeiten, Seiten löschen/umsortieren, Protokoll |
| Vorlage | — | ansehen, eigene Version starten | bearbeiten, Versionen, Protokoll |
| Version aus Vorlage | bearbeiten, abgeben | — | immer sichtbar, helfen, Protokoll, abgegebene Version mit Nachricht zurückgeben |

Admins sehen keine fremden Bücher. Unterrichtet ein Admin selbst, ordnet man ihn wie eine Lehrkraft Klassen und Fächern zu; dann sieht er genau die Bücher dieser Klassen. Hinweis: Die Sicherung enthält technisch alle Daten — wer sie herunterladen darf (Admins), hat damit Zugriff auf alles.

## Anleitungen

- [Installation mit Docker](installation/docker.md) — Linux-Server, Mini-PC, Docker Desktop, HTTPS mit Caddy
- [Installation auf Synology (Container Manager) und QNAP (Container Station)](installation/synology.md) — inkl. HTTPS über den DSM-Reverse-Proxy
- [Betrieb](installation/betrieb.md) — Updates, Sicherung, Wiederherstellung, Notfälle

## Schnellstart

Voraussetzung ist Docker mit Compose.

```bash
git clone https://github.com/steffenmai-maidigital/bildabooky.git
cd bildabooky
mkdir -p backups
docker compose up -d --build
```

Danach ist BilDaBooky unter <http://localhost:7290> erreichbar.
Erste Anmeldung als `admin`. Das erste Admin-Passwort wird beim ersten Start **zufällig erzeugt** und steht
im Protokoll des Containers (`docker compose logs app | grep -A1 "Erstes Admin"`
bzw. Container Manager → Projekt → Protokoll). Wer lieber ein eigenes
festlegt, trägt es vorher als `ADMIN_PASSWORD` in die Compose-Datei ein. Das Passwort muss bei der ersten Anmeldung geändert werden.

**Zum Ausprobieren** in `docker-compose.yml` `DEMO_MODE: "true"` setzen. Dann
entstehen zwei Lehrkräfte (`sommer` / `sommer123` und `winter` / `winter123`),
die Klasse 2b mit fünf Fächern, zwei Kinder (`mia` / `mia`, `ben` / `ben`) und
Beispielbücher. Damit sich die Kinder anmelden können, schaltet eine Lehrkraft
unter „Klassen → 🔓 Anmeldung“ die Klasse frei und gibt ihnen den Sitzungscode.

### Synology (Container Manager)

Schritt für Schritt in [installation/synology.md](installation/synology.md).
**Wichtig:** Kamera und Mikrofon erlauben Browser nur über **HTTPS**; die
Anleitung zeigt, wie das mit dem Reverse Proxy des DSM geht.

### Gestaltung der Schule

Unter **🎨 Einstellungen** (nur Admins):

1. **Schule**: Name und ein freiwilliger Text für die Fußzeile (z. B. die Anschrift).
2. **Domain** (freiwillig): die Adresse, unter der alle BilDaBooky
   erreichen, z. B. `https://buecher.meine-schule.de`. Sie steht auf den
   Anmeldekarten, und BilDaBooky baut damit alle Links. Siehe
   [Domain eintragen](#domain-eintragen) unten.
3. **Farben**: Akzent (Knöpfe), Kopfzeile, Hintergrund, mit Live-Vorschau.
   Die Schriftfarbe darauf wird automatisch hell oder dunkel gewählt, damit
   alles lesbar bleibt.
4. **Lizenz**: Lizenznehmer und Lizenzschlüssel (siehe unten).
5. **Logo**: PNG, JPG, WebP oder SVG (höchstens 2 MB). Es erscheint in der
   Kopfzeile, auf der Anmeldeseite und als Browser-Symbol.

Diese Einstellungen haben Vorrang vor `SCHOOL_NAME` und `APP_URL` aus der
Compose-Datei.

### Domain eintragen

Mit der eingetragenen Domain baut BilDaBooky **alle** Adressen: die Links auf
den Anmeldekarten, die Weiterleitung nach dem Anmelden und die Adressen der
Stylesheets, Skripte und Bilder jeder Seite.

**Darum erst eintragen, wenn die Adresse schon funktioniert.** Am sichersten
in dieser Reihenfolge:

1. Reverse Proxy und Zertifikat einrichten (siehe Anleitungen).
2. BilDaBooky über die neue Adresse öffnen, z. B.
   `https://buecher.meine-schule.de`.
3. Erst dort unter **Einstellungen → Domain** die Adresse eintragen.

**Was passiert, wenn die Adresse noch nicht funktioniert?** Dann laden die
Seiten ihre Gestaltung von einer Adresse, die nicht antwortet, und das
Anmelden leitet dorthin weiter — man kommt nicht mehr in die Einstellungen,
um es zu korrigieren. Deshalb fragt BilDaBooky nach, wenn du eine Domain
einträgst, über die du gerade **nicht** angemeldet bist („Ja, die Adresse
stimmt …“).

**Notausgang**, falls es doch passiert ist: Dieser Befehl löscht die
eingetragene Domain; danach gilt wieder `APP_URL` aus der Compose-Datei bzw.
die Adresse, über die man BilDaBooky aufruft.

```bash
sudo docker exec bildabooky php artisan bildabooky:clear-domain
```

Auf der Synology geht das auch ohne SSH: Container Manager → Container
`bildabooky` → Reiter **Terminal** → Sitzung starten →
`php artisan bildabooky:clear-domain` eingeben.

Bleibt das Feld leer, gilt einfach `APP_URL` aus der Compose-Datei.

### Sommerferienmodus

Unter **Einstellungen → 6 · Sommerferienmodus** (nur Admins):

- einschalten und das **Datum der Durchführung** wählen (frühestens morgen);
  gelöscht wird an diesem Tag um 3 Uhr nachts
- gelöscht werden **alle Schülerkonten** mit ihren Büchern (auch Versionen
  aus Vorlagen), Fotos, Tonaufnahmen und Protokolleinträgen; Lehrkräfte und
  Admins bleiben
- Optionen: Klassenbücher und Vorlagen mitlöschen (Standard: ja), die Klassen
  selbst löschen (Standard: nein), jedes Jahr am selben Tag wiederholen
- zum Einschalten ist eine Bestätigung nötig; eine Vorschau zeigt, was
  gelöscht würde
- drei Wochen vorher sehen alle einen Hinweis — Kinder werden gebeten, ihre
  Bücher als Datei mit nach Hause zu nehmen
- unmittelbar vorher entsteht eine Sicherung `…_vor-sommerferien.zip` im
  Ordner `backups` (wird nach `BACKUP_KEEP_DAYS` wie jede Sicherung gelöscht)

Ausgeführt wird über den Zeitplan-Container; läuft keiner, prüft BilDaBooky
höchstens stündlich nach einer Webanfrage. Von Hand:

```bash
docker exec bildabooky php artisan bildabooky:summer-holiday --preview
docker exec -it bildabooky php artisan bildabooky:summer-holiday --now
```

### Editionen und Lizenz

| Edition | Was |
|---|---|
| **Community Edition** | die Open-Source-Fassung. Vollständig, ohne Einschränkungen und ohne Ablaufdatum. |
| **Supported Edition** | dieselbe Software mit einer gepflegten Support-Lizenz (Standard oder Enterprise). |

Die Lizenz ist **rein informativ und hat keine funktionalen
Auswirkungen**. Admins tragen sie unter **Einstellungen → 4 · Lizenz** ein:
den Lizenznehmer und einen 20-stelligen Schlüssel. Angezeigt wird sie in der
Fußzeile unter **Hilfe**. Ein abgelaufener Support erscheint dort rot; die
Anwendung läuft trotzdem uneingeschränkt weiter.

Schema des Lizenzschlüssels (20 Zeichen):

| Stellen | Bedeutung | Beispiel |
|---|---|---|
| 1 | `1` = Standard-Lizenz, `2` = Enterprise-Lizenz | `1` |
| 2–9 | Kundennummer (8 Zeichen, Buchstaben und Zahlen) | `GSPARK01` |
| 10–15 | Support-Ende als `JJMMTT` | `270731` |
| 16–20 | Lizenzanzahl (5 Ziffern, `99999` = unlimitiert) | `00500` |

Beispiel: `1GSPARK0127073100500`. Gespeichert werden nur Schlüssel, die dem
Schema entsprechen.

### Einstellungen (Umgebungsvariablen)

| Variable | Standard | Bedeutung |
|---|---|---|
| `SCHOOL_NAME` | Unsere Grundschule | erscheint in Kopfzeile und Anmeldung |
| `APP_URL` | http://localhost:7290 | öffentliche Adresse (für Anmeldekarten). Beginnt sie mit `https://`, erzeugt BilDaBooky alle Links mit dieser Adresse und https — nötig hinter Proxy-Ketten, die intern „http“ melden (z. B. ein vorgeschalteter Proxy vor Nginx Proxy Manager). Die Domain unter Einstellungen hat Vorrang |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | admin / *(leer = zufällig, steht im Protokoll)* | erster Zugang, nur beim allerersten Start |
| `LDAP_HOST`, `LDAP_PORT`, `LDAP_SSL`, `LDAP_BASE_DN`, `LDAP_USERNAME`, `LDAP_PASSWORD`, … | *(leer)* | optionale Startwerte für das Schulverzeichnis; gepflegt wird alles unter **Einstellungen → 9** (siehe `.env.example`) |
| `REQUIRE_2FA_ROLES` | *(leer)* | Startwert: z. B. `admin,teacher` setzt die Zwei-Faktor-Stufe dieser Gruppen auf Pflicht; danach unter **Einstellungen → 8** pflegbar (nie für Kinder) |
| `DEMO_MODE` | false | legt Beispieldaten an |
| `SOURCE_URL` | *(leer)* | Link zum Quellcode in der Fußzeile (siehe Lizenz) |
| `COMMUNITY_URL` | = `SOURCE_URL` | weitere Seite für Fragen, wird unter „Hilfe → Support“ angezeigt |
| `GITHUB_URL` | GitHub-Projekt | Quellcode und Fehlermeldungen, unter „Hilfe → Support“ (leer = ausblenden) |
| `PROJECT_URL` | `https://www.mai-digital.de` | Projektseite, unter „Hilfe → Support“ (leer = ausblenden) |
| `BACKUP_KEEP_DAYS` | 14 | so lange bleiben nächtliche Sicherungen liegen |
| `LOG_DAILY_DAYS` | 30 | so lange bleiben die Tagesprotokolle liegen |
| `FRESH_DB` | 0 | `1` löscht beim Start Datenbank **und** Medien. Danach sofort wieder auf `0` stellen. |
| `TRUSTED_PROXIES` | interne Netze | Reverse Proxys, deren Angaben (echte IP, HTTPS) geglaubt werden; `*` = allen vertrauen (nicht empfohlen) |

Der `APP_KEY` wird beim ersten Start erzeugt und im Datenvolume abgelegt
(`/data/.app_key`). Damit bleiben Anmeldungen, Zwei-Faktor-Geheimnisse und
Anmeldekarten auch nach einem Neuerstellen des Containers gültig.
**Diese Datei gehört zur Sicherung.** Ohne sie lassen sich Zwei-Faktor und
hinterlegte Karten nicht mehr entschlüsseln.

### Daten und Sicherung

- `app-data` (Volume): Datenbank `bildabooky.sqlite`, Medien unter `medien/`, `.app_key`
- `./backups`: jede Nacht um 2:30 Uhr ein ZIP mit Datenbank und Medien
- Admins können unter **Klassen → Sicherung** jederzeit eine Sicherung herunterladen.

Wiederherstellen und Umziehen: siehe [installation/betrieb.md](installation/betrieb.md).

## Versionen und Docker-Abbilder

Versionsnummern folgen dem Schema **JJMMTTNN**: Datum plus laufende Nummer
des Tages. `26101101` ist die erste Version am 11.10.2026, `26101102` die
zweite an diesem Tag.

Jeder Bau erzeugt das Docker-Abbild **`bildabooky:latest`**. Eine
veröffentlichte Version bekommt zusätzlich ihre Nummer als Tag, z. B.
**`bildabooky:26101101`**. Alte Abbilder bleiben so als Rückweg erhalten
(siehe [Betrieb](installation/betrieb.md#zurück-zu-einer-älteren-version)).

Für jede Version baut GitHub das Abbild außerdem für Intel/AMD und ARM und
legt es als `ghcr.io/steffenmai-maidigital/bildabooky:<version>` und
`:latest` in die Paketablage. Solange das Repo privat ist, ist auch das
Paket privat.

Neue Versionen veröffentlichen die Betreuer; was sich in jeder Version ändert,
steht im [CHANGELOG](CHANGELOG.md).

## Entwicklung

```bash
docker build -t bildabooky:dev .
docker run --rm -p 7290:80 -e DEMO_MODE=true -e APP_DEBUG=true bildabooky:dev
```

Aufbau:

```
app/Models          Book, Page, Media, SchoolClass, User (Rollen, Rechte, 2FA)
app/Http/Controllers  Bücher, Seiten, Medien, Benutzer, Klassen, Konto, Anmeldung
app/Support         Totp (RFC 6238), Backup, Lang, Branding, License
public/js/book.js   Seitendarstellung (Editor, Leser, Druck, Datei zum Mitnehmen)
public/js/editor.js Editor
public/js/reader.js Leseansicht
lang/*.json         Übersetzungen (deutsche Texte sind die Schlüssel)
```

Ohne Build-Schritt: kein Node und kein npm. CSS und JavaScript liegen direkt in
`public/`.

### Übersetzen

Alle Oberflächentexte sind auf Deutsch geschrieben und dienen als Schlüssel.
Für eine neue Sprache:

1. `lang/en.json` nach `lang/xx.json` kopieren und die Werte übersetzen.
2. In `config/bildabooky.php` unter `sprachen` eintragen.
3. Platzhalter wie `:name` unverändert übernehmen. Vor jeder Version prüfen
   die Betreuer, ob alles übersetzt ist.

Fehlende Texte erscheinen automatisch auf Deutsch.

## Mitmachen

Siehe [CONTRIBUTING.md](CONTRIBUTING.md). Sicherheitslücken bitte nicht
öffentlich melden, siehe [SECURITY.md](SECURITY.md).

## Lizenz

[GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0-or-later).

Ihr dürft BilDaBooky frei nutzen, verändern und weitergeben. Wer eine
**veränderte** Fassung als Webdienst betreibt, muss den Nutzern den Quellcode
dieser Fassung anbieten. Dafür gibt es `SOURCE_URL`: Der Link erscheint in der
Fußzeile.

**Sticker und Symbole:** [Twemoji](https://github.com/jdecked/twemoji)
© Twitter, Inc und weitere Mitwirkende, lizenziert unter
[CC-BY 4.0](https://creativecommons.org/licenses/by/4.0/)
(`public/img/emoji/`, siehe die dortige `LIZENZ.txt`).

