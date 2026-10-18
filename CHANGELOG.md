# Changelog

Format angelehnt an „Keep a Changelog“. Versionsnummern im Schema
**JJMMTTNN**: Datum plus laufende Nummer des Tages (26101101 = erste
Version am 11.10.2026). Neue Einträge kommen unter „Unveröffentlicht“;
`tools/release.sh` macht daraus die nächste Version.

## [Unveröffentlicht]

## [26101802] — 2026-10-18

### Dokumentation

- Beiträge: Fehler, Ideen und Fragen als Issue melden; Pull Requests sind derzeit nur für eingeladene Mitwirkende möglich (`CONTRIBUTING.md`).

## [26101801] — 2026-10-18

### Neu

- Rua lässt sich verschieben: mit Maus oder Finger an jede Stelle ziehen, der Browser merkt sich die Lage (auch über Seitenwechsel und Größenänderungen). Ein kurzer Tipp befragt ihn weiter; die Sprechblase klappt zur freien Seite.
- Rua lobt passend zum Anlass: nach Löschen, Passwort, Zwei-Faktor, Token, Sicherung, Abbrechen oder Abmelden nur schlicht „Erledigt.“, sonst wie bisher mit Bild. Erkannt wird der Anlass von einer neuen Middleware (`FoxOccasion`) an Methode und Route der abgeschickten Aktion.
- Rua hilft bei Fehlern und beim Warten: Kommt eine Seite mit einer Fehlermeldung zurück, sagt er „Gegenwind. Versuch es nochmal, ich bleib an Deck.“ oder „Da fehlt noch etwas. Schau dir das markierte Feld an.“ (auf Anmelde-, Konto-, Passwort- und Zwei-Faktor-Seiten ohne Bild); dauert das Absenden eines Formulars länger als 0,7 s, sagt er „Die Ruder sind im Wasser …“. Formulare mit `data-no-fox` lässt er in Ruhe.

### Geändert

- Repository nach den Gepflogenheiten offener Projekte: Verhaltenskodex (`CODE_OF_CONDUCT.md`), Vorlagen für Fehlermeldungen, Vorschläge und Pull Requests; Sicherheitslücken lassen sich direkt vertraulich melden. Die Werkzeuge der Betreuer (Release, Tests, Prüfungen) liegen nicht mehr im öffentlichen Repo.
- **Englische Namen im Code (Standard 10):** Klassen, Methoden, Variablen, Routen, Ansichten, CSS-/JS-Namen, Datenbankspalten, gespeicherte Werte, Einstellungsschlüssel, Befehle und Dateien heißen jetzt englisch; die Oberfläche bleibt unverändert (Deutsch, Englisch, Französisch, Spanisch). Die vollständige Liste alt → neu steht in `docs/RENAMING.md`.
  - **Automatische Umstellung:** Die neue Migration `rename_to_english` stellt beim ersten Start die Datenbank um: Spalten (`users.fuchs` → `users.fox`, `books.freigabe` → `books.sharing`, `book_logs.seite`/`aktion` → `page`/`action`, `class_leader.klassenlehrkraft` → `is_class_teacher`, `class_session_extensions.alt_ende`/`neu_ende` → `old_end`/`new_end`), gespeicherte Werte (Rollen `leiter`/`ersteller` → `teacher`/`pupil`, Buchart `eigen`/`klasse`/`vorlage` → `own`/`class`/`template`, Teilen `privat`/`klasse`/`lehrkraft` → `private`/`class`/`teacher`, Format `hoch`/`quadrat`/`quer` → `portrait`/`square`/`landscape`, Medien `bild`/`ton` → `image`/`audio`, Schriften und Formen auf den Seiten, Zwei-Faktor-Stufen `pflicht`/`aus` → `required`/`off`), Einstellungsschlüssel (z. B. `fuchs_aktiv` → `fox_enabled`, `sommer_datum` → `summer_date`, `sitzungspflicht` → `session_required`, `ldap_gruppe_leiter` → `ldap_group_teacher`, `mfa_leiter` → `mfa_teacher`, `impressum_link` → `imprint_url`) und die Einträge des Änderungsprotokolls. Sie ist idempotent: auch eine alte Sicherung wird beim nächsten Start nachgezogen. `app:upgrade` legt vorher wie immer eine Sicherung an.
  - **Neue Adressen, alte leiten um:** z. B. `/anmelden` → `/login`, `/buecher/…` → `/books/…`, `/klassen` → `/classes`, `/benutzer` → `/users`, `/einstellungen` → `/settings`, `/konto` → `/account`, `/hilfe` → `/help`, `/impressum` → `/imprint`, `/datenschutz` → `/privacy`, Handbücher `/hilfe/handbuch/benutzer` → `/help/manual/user`. Alle alten Seitenadressen leiten dauerhaft (301) um, die Abfrage bleibt erhalten (alte Parameter wie `?klasse=` gelten weiter) — Lesezeichen, Anmeldekarten und Links bleiben gültig.
  - **Einmal neu anmelden:** Die Daten der Sitzung haben neue Namen; nach dem Update meldet sich jeder einmal neu an.
  - **Befehle:** `bildabooky:admin-passwort` heißt jetzt `bildabooky:admin-password` (Option `--2fa-aus` → `--disable-2fa`), `bildabooky:sicherung` → `bildabooky:backup`, `bildabooky:domain-leeren` → `bildabooky:clear-domain`, `bildabooky:sommerferien` → `bildabooky:summer-holiday` (Optionen `--vorschau`/`--jetzt` → `--preview`/`--now`). Die alten Namen und Optionen gelten weiter.
  - Was der Browser sich merkt (Rua schläft, Lage, eingeklappte Bereiche, zuletzt benutzte Sticker, Sprache), wird beim ersten Aufruf unter dem neuen Namen übernommen.
- Anleitungen: Abschnitt zu einem Tunnel-Dienst entfernt; die Hilfe bei „Zu viele Weiterleitungen“ ist jetzt allgemein für vorgeschaltete Proxys formuliert.
- **Der Fuchs heißt jetzt Rua:** ein Wikinger-Fuchs aus einem norwegischen Fjorddorf, den die Iren in Dyflin „Rua“ (der Rote) nannten. Alle Fuchs-Texte sind neu in seiner Stimme — kurz, warm, ermutigend, mit höchstens einem Bild vom Meer; bei Passwörtern, Zwei-Faktor und Datenschutz nüchtern. Auf der Anmeldeseite stellt er sich vor. Neue Zeichnung mit Umhang, Fibel, Runen-Amulett und schiefem Ohr (aus dem gemeinsamen Standard). Übersetzungen in Englisch, Französisch und Spanisch angepasst.

### Behoben

- Rua hat keinen grauen Kasten mehr hinter sich: allgemeine Knopf-Regeln (z. B. im Dunkelmodus) gaben der Figur eine Fläche; jetzt hat sie ausdrücklich keine.
- **Fuchs reagiert wieder auf Klicks und Fingertipps:** `.fox-figure { all: unset }` setzte auch `pointer-events` zurück, die Figur erbte `none` vom Rahmen – echte Klicks gingen durch den Fuchs hindurch (Skript-Klicks per `.click()` zeigten den Fehler nicht). `pointer-events: auto` steht jetzt direkt dahinter.

## [26101701] — 2026-10-17

### Neu
- **Zwei-Faktor-Richtlinie je Gruppe** (Einstellungen → 8): für Admins und Lehrkräfte getrennt Pflicht, wählbar oder aus; Gruppen des Schulverzeichnisses mit Pflicht; Ausnahme je Person unter Benutzer → Bearbeiten. `REQUIRE_2FA_ROLES` ist nur noch der Startwert. Schulkonten bestätigen Ausschalten und neue Wiederherstellungscodes mit einem aktuellen App-Code statt Passwort.
- **Handbücher** unter Hilfe → Handbücher: Benutzer, Administrator, Architektur, Sicherheit, Überblick (neu unter `docs/`), als formatierte Seiten; Hilfe-Seite nach dem gemeinsamen Standard (System zeigt Anmeldeart und eigenes Konto, Changelog als Seite).
- **Anmeldung über das Schulverzeichnis** (Standard 09): Lehrkräfte und Admins können sich zusätzlich mit ihrem Schulkonto anmelden — Active Directory, Samba, Univention oder OpenLDAP. Einrichtung unter **Einstellungen → 9** (Server, Port, LDAPS/STARTTLS, Suchbasis, Servicekonto, Gruppe der Lehrkräfte, Gruppe der Admins) mit Knopf **„Verbindung testen“**. Das Konto entsteht beim ersten Anmelden; Rolle (Lehrkraft/Admin) kommt aus den Gruppen, Klassen und Fächer ordnet der Admin wie gewohnt zu. Kinder haben immer eigene Konten; eigene Konten für Lehrkräfte funktionieren weiterhin.
- Benutzerliste zeigt Schulkonten mit Kennzeichen; für sie gibt es kein Passwort und keine Anmeldekarte in BilDaBooky, unter **Konto** entfällt „Passwort ändern“. **Hilfe → System** zeigt die Anmeldeart.
- `werkzeuge/symbole.py` prüft, dass es für jedes Emoji der Oberfläche eine Twemoji-Grafik gibt (gemeinsamer Standard für alle Projekte).
- Hell/Dunkel: Umschalter (◐) oben rechts; ohne Wahl folgt die Oberfläche der Systemeinstellung. Editor, Leseansicht, Anmeldekarten und Druck bleiben hell.
- Testsystem-Kennzeichnung: `TEST_MODE: "true"` zeigt oben das Banner **TESTSYSTEM** und einen Hinweis unter **Hilfe → System**.
- Downgrade-Schutz beim Start (`php artisan app:upgrade`): Versionsabgleich, Sicherung der Datenbank nach `/data/backups/` vor jeder Änderung, Migrationen. Ist die Datenbank neuer als das Abbild, startet der Container nicht.

### Geändert
- Das Docker-Abbild enthält die PHP-Erweiterung `ldap`.
- Nummerierung der Einstellungen: 9 Anmeldung über das Schulverzeichnis, 10 Impressum und Datenschutz.
- Einstellungen für das Erscheinungsbild heißen wie in den anderen Projekten (`brand_company_name`, `brand_logo_path`, `brand_footer_text`, `brand_accent_color`, `brand_header_color`, `brand_background_color`). Vorhandene Werte werden beim Update automatisch übernommen.
- Der Scheduler läuft als `www-data`.

### Behoben
- Docker-Bau: Leserechte der Dateien werden im Abbild gesetzt — auch wenn der Bauhost die Dateien mit Modus 600 ablegt (sonst „Forbidden“ wegen unlesbarer `.htaccess`).

### Dokumentation
- Anleitungen nach `installation/` verschoben (`docker.md`, `synology.md`, `betrieb.md`, englisch unter `installation/en/`), neu: `reverse-proxy.md` für DSM, Nginx Proxy Manager, nginx, Caddy und Cloudflare Tunnel.

## [26101105] — 2026-10-11

### Geändert
- `APP_URL` mit `https://` wird jetzt als feste öffentliche Adresse verwendet: Alle Links (CSS, JS, Formulare, Anmeldekarten) entstehen mit dieser Adresse und https — auch hinter Proxy-Ketten, die intern „http“ melden (z. B. ein vorgeschalteter Proxy vor Nginx Proxy Manager). Bisher galt das nur für die Domain aus den Einstellungen, die weiterhin Vorrang hat. Mit `http://…` (Standard) bleibt alles wie bisher.

### Dokumentation
- Synology-Anleitung: Hilfe bei „Zu viele Weiterleitungen“ hinter mehreren Proxys und bei Seiten ohne Gestaltung hinter Proxys.

## [26101104] — 2026-10-11

### Neu
- Klassenlehrkraft: Der Admin kennzeichnet beim Anlegen einer Klasse oder später unter „Klasse bearbeiten“ die verantwortliche Lehrkraft (★, auch mehrere je Klasse; eine Lehrkraft kann es in mehreren Klassen sein). Reine Kennzeichnung ohne zusätzliche Rechte: Sie steht in der Klassenliste vorne, in den Klassenbereichen der Lehrkräfte und bei den Kindern oben in der Bibliothek („Klasse 4a · ★ Klassenlehrkraft: …“).
- Klasse löschen löscht jetzt auch ihre Kinder mit allen Büchern, Fotos, Tonaufnahmen und Protokolleinträgen sowie Klassenbücher und Aufgaben der Klasse. Bestätigung durch Eintippen des Klassennamens, vorher automatische Sicherung; Lehrkräfte und ihre eigenen Bücher bleiben.
- Sommerferienmodus löscht standardmäßig auch die Klassen (abwählbar): Das neue Schuljahr beginnt leer, Lehrkräfte, Admins und Fächer bleiben erhalten.
- Lehrkräfte tragen sich selbst in Klassen ein („Klassen → Weitere Klassen → Hier unterrichte ich“), wählen ihre Fächer, ändern sie später („Meine Fächer“) oder tragen sich aus. In den Einstellungen abschaltbar.
- Anmelde-Sitzungen für Kinder: Lehrkräfte der Klasse schalten die Anmeldung frei (60, 120, 180 Minuten oder bis zu einem Tag in den nächsten 7 Tagen, dann bis 17 Uhr) — mit Pflicht-Grund. Kinder melden sich mit Passwort und dem 6-stelligen Sitzungscode an; das ersetzt bei ihnen die Zwei-Faktor-Anmeldung (Lehrkräfte und Admins behalten sie). Alle Lehrkräfte der Klasse sehen laufende Sitzung, Code und angemeldete Kinder und können sie mit Grund vorzeitig beenden — die Kinder werden dann abgemeldet. Verlauf mit Gründen und Anmeldungen 60 Tage, danach automatisch gelöscht. Status in der Bibliothek der Lehrkraft und in der Klassenliste; in den Einstellungen abschaltbar. Code-Raten wird gebremst (5 Versuche je Kind, 60 je Sitzung und Stunde). Angemeldete Kinder sehen oben neben „Abmelden“ (auch im Editor und beim Lesen) die Restzeit als ablaufende Uhr, in den letzten 5 Minuten rot; bei 0 werden sie abgemeldet. Ist die Klasse nicht freigeschaltet, steht im Anmeldefeld deutlich „Für deine Klasse ist keine Sitzung freigegeben“. Lehrkräfte der Klasse können eine laufende Sitzung verlängern (+30/+60/+120 Minuten oder bis zu einem Tag in den nächsten 7 Tagen, 17 Uhr); Code und Anmeldungen bleiben, jede Verlängerung wird mit Lehrkraft und Zeiten dokumentiert.
- Fächer: Admins legen Fächer mit Symbol an (Klassen → Fächer) und stellen je Klasse unter „Fächer und Lehrkräfte“ in einer Tabelle ein, welche Fächer die Klasse hat und welche Lehrkraft welches Fach unterrichtet. Eine Klasse kann mehrere Lehrkräfte haben, eine Lehrkraft mehrere Klassen mit unterschiedlichen Fächern; wer ein Fach unterrichtet, gehört automatisch zur Klasse.
- Kinder sehen ihre Bibliothek nach Fächern geordnet (Aufgaben, Klassenbücher, eigene Bücher, aus der Klasse), legen Bücher direkt im Fach an und verschieben sie über „⋯ → In anderes Fach“ oder in den Buch-Einstellungen. Bücher ohne Fach stehen unter „Ohne Fach“.
- Lehrkräfte sehen weiterhin alle Bücher ihrer Klassen, jetzt mit Fach-Filter (eigene Fächer zuerst und markiert) und Fach-Abzeichen auf den Karten. Versionen folgen dem Fach ihrer Aufgabe. Fachwechsel stehen im Änderungsprotokoll.
- Demo: zweite Lehrkraft (`winter` / `winter123`) und fünf Fächer in Klasse 2b.
- Sticker: rund 900 statt 120, sortiert in zehn Gruppen mit Reitern (Gesichter, Menschen, Tiere, Natur & Wetter, Essen, Orte & Fahrzeuge, Sport & Spiel, Schule & Dinge, Feste, Zeichen) und dem Reiter „Zuletzt benutzt“. Nur Emojis bis Unicode 12, damit auch ältere Tablets sie kennen.
- Sticker sehen jetzt auf jedem Gerät gleich aus (iPad, Android, Windows, PDF und Datei zum Mitnehmen): Sie kommen als Grafik aus Twemoji (CC-BY 4.0), die BilDaBooky selbst ausliefert — ohne fremde Server. Hinweis auf die Urheber unter „Hilfe“.
- Editor: Die Knöpfe unter „Anordnen“ sind beschriftet und erklärt („Ganz nach vorne – liegt über allem anderen“ usw.) statt nur Symbole. Was gerade nicht geht, ist ausgegraut; „Gerade stellen“ erscheint nur bei gedrehten Teilen, „Löschen“ ist rot. Die Symbole dort und bei „Anhören“ und „Spiegeln“ kommen aus derselben Twemoji-Sammlung wie die Sticker und sehen überall gleich aus.
- Editor: Auch die Werkzeugleiste (Foto, Kamera, Malen, Ton, Sticker, Form, Seite) zeigt die einheitlichen Twemoji-Symbole.
- Einheitliche Symbole in der ganzen Oberfläche: Menüs, Knöpfe, Überschriften, Dialoge, Fuchs-Blase und Fehlerseiten zeigen ihre Emojis als Twemoji-Grafik (js/symbols.js). Buchseiten und Eingabefelder bleiben unberührt, reine Textzeichen wie → und ✓ ebenso.
- Mehr Symbole: Knöpfe (📖 Lesen, ✏️ Bearbeiten, 💾 Speichern, ➕ Anlegen, 🗑️ Löschen, 🚪 Abmelden …), Seitenüberschriften (👤 Mein Konto, 🏫 Klassen, 🔐 Zwei-Faktor …) und die Bereiche der Bibliothek (📘 Meine Bücher, 👥 Klassenbücher, 📝 Aufgaben, 🤝 geteilt, 📚 alle Bücher) tragen jetzt ein Symbol; „Lesen“ im Editor zeigt 📖.
- Hilfe → Support: Hinweis auf das GitHub-Projekt (Quellcode, Fehler melden) und die Projektseite, mit der Bitte, keine Kinderdaten in öffentliche Fehlermeldungen zu schreiben. Adressen über `GITHUB_URL` und `PROJECT_URL` änderbar.
- Impressum und Datenschutz: Admins schalten beide Seiten unter „Einstellungen → 8“ einzeln ein und aus, entweder mit eigenem Text (einfache Formatierung, Links automatisch, kein HTML) oder als Link auf eine vorhandene Seite der Schulhomepage. Eingeschaltete Seiten sind ohne Anmeldung erreichbar (`/impressum`, `/datenschutz`) und stehen auf der Anmeldeseite und in der Fußzeile. Für den Datenschutz gibt es eine Vorlage, die beschreibt, was BilDaBooky tatsächlich speichert.
- Editor am PC: Tooltips beim Draufzeigen mit Name, kurzer Erklärung und Tastenkürzel (Mac: ⌘), Farbknöpfe mit Farbnamen. Nur mit Maus oder Trackpad; an Tablet und Handy erscheinen keine. Knöpfe, deren Beschriftung schon alles sagt, bekommen keinen.
- Editor an Tablet und Handy: Knopf lange drücken zeigt dieselbe Erklärung (größer, über dem Finger); beim Loslassen löst der Knopf dann nicht aus.
- Editor überall: ❓ in der Kopfzeile schaltet den Erklär-Modus ein — jeder angetippte Knopf erklärt sich, statt etwas zu tun; nochmal ❓ oder Esc beendet ihn. Der Fuchs verrät beides einmal je Gerät, solange noch keine Erklärung abgerufen wurde.
- Zurückgeben: Lehrkräfte geben eine abgegebene Version in „Versionen der Kinder“ oder in der Leseansicht mit „↩️ Zurückgeben“ zurück, auf Wunsch mit einer kurzen Nachricht. Das Kind sieht sie oben im Editor, in der Leseansicht und als „↩️ zurückgegeben“ auf der Buchkarte, bis es wieder abgibt; der Fuchs weist darauf hin. Die Rückgabe steht im Änderungsprotokoll. Neue Spalten `returned_at`, `return_note`, `returned_by` (Migration läuft beim Start automatisch).

### Geändert
- Admin als Haken: Im Benutzerformular wählt man nur noch Lehrkraft oder Schüler; bei Lehrkräften macht der Haken „Zusätzlich Admin“ sie zu Admins (Benutzer, Klassen, Fächer, Einstellungen pflegen). Ihre Klassen und Fächer bleiben dabei erhalten. Vorhandene Admins erscheinen als Lehrkraft mit Haken; der letzte Admin kann den Haken nicht verlieren.
- **Admins sehen keine fremden Bücher mehr.** Admin ist eine Verwaltungsrolle (Benutzer, Klassen, Fächer, Einstellungen). Zugriff auf Bücher gibt es für Lehrkräfte und Admins gleichermaßen nur über die Klassen, in denen sie unterrichten; Admins lassen sich dafür wie Lehrkräfte Klassen und Fächern zuordnen („Unterrichtet in diesen Klassen“, Tabelle „Fächer und Lehrkräfte“). Gilt für Bibliothek, Lesen, Bearbeiten, Vorschau, Medien, Protokoll, Versionen und Zurückgeben.
- Bibliothek: Vorschaubilder werden erst geladen, wenn die Karten in Sicht kommen bzw. ein Bereich aufgeklappt wird — gebündelt über `/vorschau` (bis 40 je Abruf, Leserecht je Buch geprüft). Bei 260 Büchern zeichnet die Seite beim Öffnen nur noch die ersten statt aller Vorschauen samt Fotos.
- Bibliothek der Lehrkraft neu geordnet: je Klasse „Klassenbücher und Aufgaben“ (in zwei Gruppen) und „Bücher der Kinder“ — alphabetisch nach Kindern, je Kind einklappbar mit Zählern (Bücher, abgegeben) und den Gruppen 📝 Aufgaben (abgegeben → zurückgegeben → in Arbeit), 🤝 Geteilt (Klasse vor Lehrkraft) und 🔒 Nicht geteilt. Filter (Alle, Aufgaben, Abgegeben, Geteilt, Nicht geteilt), „Alle auf-/einklappen“ und eine Zeile „Noch ohne Bücher“.
- Abgeben: Der Knopf sitzt jetzt oben in der Leiste — im Editor neben „Lesen“, in der Leseansicht neben „Bearbeiten“ — statt in den Buch-Einstellungen. Er zeigt den Zustand („✅ Abgeben“ / „📬 Abgegeben“), fragt vor dem Abgeben und Zurücknehmen kurz nach und erscheint nur bei der eigenen Version einer Vorlage.
- Docker-Abbild: Die beim Bauen übergebene Versionsnummer (`--build-arg VERSION`, so bauen GitHub und `tools/release.sh`) wird in `config/version.php` geschrieben. Fußzeile und Hilfe zeigen damit immer die Version des laufenden Abbilds.

### Behoben
- Klassenliste: Der Klassenname war im schmalen Eingabefeld nicht lesbar. Umbenennen, Lehrkräfte und Fächer sowie Löschen liegen jetzt auf einer eigenen Seite „Klasse bearbeiten“; in der Liste steht der Name gut lesbar, daneben nur Anmeldung, Bearbeiten, Schüler und Karten.
- Klassenliste: Die Spalten verrutschten (die Fächer-Zelle war keine richtige Tabellenzelle); Lehrkräfte stehen jetzt untereinander, Knöpfe brechen sauber um.
- Aufgaben aus Sicht des Kindes: Die Karte zeigt jetzt die Vorschau der eigenen Version (statt der leeren Vorlage) und öffnet sie beim Antippen; abgegebene Versionen tragen einen Stempel „✓ Abgegeben“ mit Datum, zurückgegebene „↩ zurückgegeben“ — auch in den Ansichten der Lehrkraft. Die eigene Version steht nicht mehr doppelt unter „Meine Bücher“.
- Aufgabenkarte des Kindes: „Ansehen“, „Drucken / PDF“ und „Als Datei mitnehmen“ beziehen sich auf die eigene Version statt auf die leere Vorlage; neu „Meine Version löschen“ (zum Neuanfangen); das Kennzeichen heißt für Kinder „📝 Aufgabe“.
- Bibliothek: Ein Klick irgendwo auf der Seite klappte ganze Bereiche mit Buchkarten zu (das Schließen der „⋯“-Menüs griff zu weit).
- Der Auswahlrahmen einer Form saß versetzt und zu klein.
- Tonknopf in der Leseansicht und in der Datei zum Mitnehmen: Eine allgemeine Knopfregel machte ihn zu einem kleinen, kaum treffbaren Knopf oben links. Er ist jetzt überall rund und füllt seine Fläche.
- Ein Tonknopf ließ sich nicht antippen, wenn ein Sticker, Text oder Bild mit seinem Rand darüberlag. Beim Lesen nehmen jetzt nur Tonknöpfe Klicks an; im Editor greift ein Sticker nur mit seinem sichtbaren Bild.
- Tonknopf zeigt immer einen Lautsprecher (aufrecht, auch wenn der Knopf gedreht ist); beim Abspielen pulsiert er und die Schallwellen laufen. Im Editor spielt nochmaliges Antippen den Ton ab.

## [26101103] — 2026-10-11

### Sicherheit
- Passwort-Raten ließ sich mit gefälschten IP-Angaben umgehen: zusätzliche
  Sperre je Benutzername (10 Fehlversuche → 30 Minuten), unabhängig von der
  IP; Zurücksetzen durch die Lehrkraft hebt sie auf. Proxy-Angaben werden
  standardmäßig nur noch aus internen Netzen geglaubt (`TRUSTED_PROXIES`).
- Sicherheits-Header für alle Seiten: Content-Security-Policy, Schutz gegen
  Einbetten (Clickjacking), `nosniff`, Referrer- und Permissions-Policy;
  hinter HTTPS zusätzlich `Secure`-Cookies und HSTS.
- Kein Standardpasswort mehr: Ohne `ADMIN_PASSWORD` entsteht beim ersten
  Start ein zufälliges Admin-Passwort (steht im Container-Protokoll).
- Kinder-Passwörter aus einer größeren Wortliste (rund 9.000 Möglichkeiten).
- Laravels ungenutzte Dateiauslieferung `/storage/…` abgeschaltet; Uploads
  auf 60 je Minute begrenzt.
- Neuer Sicherheitstest `tools/security_test.py` (58 Prüfungen).

### Geändert
- Bibliothek der Lehrkraft neu geordnet: je Klasse „Klassenbücher und Aufgaben“ (in zwei Gruppen) und „Bücher der Kinder“ — alphabetisch nach Kindern, je Kind einklappbar mit Zählern (Bücher, abgegeben) und den Gruppen 📝 Aufgaben (abgegeben → zurückgegeben → in Arbeit), 🤝 Geteilt (Klasse vor Lehrkraft) und 🔒 Nicht geteilt. Filter (Alle, Aufgaben, Abgegeben, Geteilt, Nicht geteilt), „Alle auf-/einklappen“ und eine Zeile „Noch ohne Bücher“.
- Domain-Einstellung verständlicher erklärt (README „Domain eintragen“,
  Installationsanleitungen, Hinweis auf der Einstellungsseite): erst
  eintragen, wenn die Adresse schon funktioniert; Notausgang auch ohne SSH
  über das Terminal im Container Manager.

## [26101102] — 2026-10-11

### Hinzugefügt
- README vollständig auf Englisch (`README.en.md`); `SECURITY.md` durchgehend
  zweisprachig, mit unterstützten Versionen und Hinweisen für den Betrieb.

### Geändert
- Bibliothek der Lehrkraft neu geordnet: je Klasse „Klassenbücher und Aufgaben“ (in zwei Gruppen) und „Bücher der Kinder“ — alphabetisch nach Kindern, je Kind einklappbar mit Zählern (Bücher, abgegeben) und den Gruppen 📝 Aufgaben (abgegeben → zurückgegeben → in Arbeit), 🤝 Geteilt (Klasse vor Lehrkraft) und 🔒 Nicht geteilt. Filter (Alle, Aufgaben, Abgegeben, Geteilt, Nicht geteilt), „Alle auf-/einklappen“ und eine Zeile „Noch ohne Bücher“.
- README: echte Repo-Adresse im Schnellstart, interne Verweise entfernt.

## [26101101] — 2026-10-11

Erste Veröffentlichung als **BilDaBooky Community Edition**.

### Hinzugefügt
- Versionsnummern im Schema JJMMTTNN; `tools/release.sh` setzt Version,
  Git-Tag und baut das Docker-Abbild als `bildabooky:<version>` und
  `bildabooky:latest`; GitHub-Action legt es für amd64 und arm64 unter
  `ghcr.io/steffenmai-maidigital/bildabooky` ab.
- Anleitungen für Docker, Synology Container Manager / QNAP Container
  Station und den Betrieb (`docs/`, englisch unter `docs/en/`); Befehl `bildabooky:admin-passwort` für
  Notfälle; Sicherungen enthalten jetzt auch Schullogo und `.app_key`.
- **Klassenbücher**: die ganze Klasse schreibt gemeinsam; Seitenversionen
  erkennen gleichzeitiges Speichern, Änderungen werden je Element
  zusammengeführt statt überschrieben; Abgleich alle 4 Sekunden und
  Anzeige, wer gerade auf welcher Seite arbeitet. Seiten löschen und
  umsortieren nur durch die Lehrkraft.
- **Vorlagen**: jedes Kind legt seine eigene Version an, kann sie abgeben;
  Übersicht „Versionen der Kinder“ für die Lehrkraft.
- **Teilen** mit zwei Knöpfen: mit der Lehrkraft (Bereich „Mit dir geteilt“)
  oder mit der Klasse (alle Kinder lesen mit). Lehrkräfte sehen alle Bücher
  ihrer Klasse.
- **Fuchs-Helfer**: eigene, animierte Fuchsfigur (SVG, kein Markenlogo) mit
  Begrüßung nach Tageszeit, Lob, Mutmach-Sprüchen und Tipps je Rolle und
  Seite, in allen vier Sprachen; zentral abschaltbar (Einstellungen → 7) und
  je Person im Konto. Im Editor sitzt er unten in der rechten Spalte und
  reagiert auf das Geschehen (Auswahl, Malen, Foto, Ton, neue Seite,
  Rückgängig, Löschen, Abgabe, Lob nach 5/10/20 Seiten); jeder Hinweis einmal
  je Sitzung und Buch, mindestens 25 Sekunden Abstand. In der Leseansicht
  begleitet er beim Blättern (Einstieg, Seiten mit Ton, Ende des Buchs).
  „Schlafen legen“: er legt sich hin, macht z-z-Z und bleibt auch über
  Seitenwechsel schlafend liegen, bis man ihn antippt. Auf der Anmeldeseite
  wirbt er für freie Software (Datenschutz, keine Kosten, offener Quellcode,
  Mitmachen) mit Link zum Quellcode und hilft beim Anmelden; sobald jemand
  in ein Feld tippt, schweigt er.
- Bibliothek: jeder Bereich (z. B. „Klasse 2b · Mit dir geteilt“) lässt sich
  einklappen, mit Anzahl der Bücher; der Zustand wird im Browser gemerkt.
- Rückfrage, wenn eine Lehrkraft das Teilen eines Kinderbuchs aufhebt.
- Eigene, mehrsprachige Fehlerseiten (403, 404, 419, 429, 500, 503) im
  Schul-Design statt der englischen Standardseiten.
- Oberfläche vereinheitlicht: Buchkarten mit Menü „⋯“ auf dem Titelbild und
  kompakter Teilen-Zeile, Anrede von Erwachsenen mit vollem Namen,
  Benutzerliste vor den Anlege-Formularen, Link „Bücher“ je Klasse,
  Protokoll-Symbol im Editor, Zwei-Faktor im Kinderkonto nur bei Bedarf.
- **Sommerferienmodus**: Admins setzen ein Datum; dann werden alle
  Schülerkonten mit Büchern, Medien und Protokolleinträgen gelöscht —
  Sicherung vorher, Hinweis drei Wochen vorher, optional jährlich;
  Befehl `bildabooky:sommerferien` (`--vorschau`, `--jetzt`).
- **Änderungsprotokoll** je Buch (wer, wann, welche Seite, was), Änderungen
  derselben Person an derselben Seite innerhalb von 10 Minuten gebündelt;
  nur für Lehrkräfte und Admins.
- SQLite im WAL-Modus mit Wartezeit und sofort sperrenden Transaktionen für
  gleichzeitiges Arbeiten.
- Gestaltung durch Admins: Schulname, Logo, Farben mit Live-Vorschau und
  automatischem Kontrast, Fußzeilentext, öffentliche Domain mit Schutz vor dem
  Aussperren (`bildabooky:domain-leeren`).
- Hilfe-Seite (Fußzeile) nach dem CrewGate-Muster „Support & Lizenz“:
  Kurzanleitung, System (Produkt, Edition, Version, Build, Systemmodus),
  Lizenz, letzte Fehler für Admins (nur die letzten 30 Tage, mit Knopf
  „Protokoll leeren“), Changelog, Support. Tagesprotokolle werden 30 Tage
  aufbewahrt (`LOG_DAILY_DAYS`).
- Lizenz nach dem CrewGate-Schema (20 Zeichen: Standard/Enterprise,
  Kundennummer, Support-Ende JJMMTT, Anzahl) unter Einstellungen → Lizenz —
  rein informativ, ohne funktionale Auswirkungen; ohne Lizenz
  „Community Edition“, mit Lizenz „Supported Edition“.
- Buch-Editor: Text, Fotos, Kamera, Malen, Tonaufnahme, Sticker, Formen,
  Seitenfarben; Ziehen, Größe, Drehen, Ebenen, Doppeln, Rückgängig;
  automatisches Speichern; Telefon- und Tablet-Ansicht.
- Formate Hochformat, Quadrat und Querformat.
- Leseansicht mit Blättern, Wischen, Vollbild und Tönen.
- Klassenbibliothek mit Freigabe, Kopie, Drucken/PDF, Datei zum Mitnehmen.
- Rollen Admin, Leiter (Lehrkraft) und Ersteller (Schüler); Klassen;
  Vor- und Nachname; Sammelanlage ganzer Klassenlisten.
- Optionale Anmeldekarten mit verschlüsselt hinterlegtem Passwort.
- Zwei-Faktor-Anmeldung (TOTP) mit Wiederherstellungscodes, auf Wunsch Pflicht
  je Rolle; Bremse gegen Passwort-Raten.
- Oberfläche auf Deutsch, Englisch, Französisch und Spanisch mit
  Browser-Erkennung, Umschalter und Rückfall auf Deutsch.
- Docker/SQLite-Betrieb mit nächtlicher Sicherung und Sicherung zum
  Herunterladen.
