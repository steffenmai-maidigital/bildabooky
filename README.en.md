# 📚 BilDaBooky — Community Edition

*Deutsche Fassung: [README.md](README.md)*

**A free, self-hosted book workshop for school children.**
The *Community Edition* is the complete open-source version (AGPL-3.0).
Children create their own books with text, photos, drawings, voice recordings
and stickers — on a tablet, laptop or phone. All data stays on the school's
server. There is no cloud, no tracking, no third-party fonts and no ads.

---

## Why?

Many parents don't want their children's photos, voices and texts to be
stored with a provider abroad. That's why BilDaBooky runs entirely on your
own server (e.g. a Synology NAS on the school network):

- **no external services**: no CDNs, Google Fonts or analytics scripts.
  The browser only loads files from the school server.
- **photos are shrunk in the browser**, which also strips location data
  (EXIF/GPS) before anything is uploaded.
- **media are not public**: the server only delivers pictures and sounds to
  logged-in people who are allowed to see the book.
- **passwords** are stored only as bcrypt hashes. Two-factor login with an
  authenticator app (TOTP) is optional. Teachers and admins can optionally
  sign in with their **school account** (Active Directory, Samba, Univention,
  OpenLDAP) — children always have their own accounts.
- **books to take home**: every book can be downloaded as *one* HTML file
  (with all pictures and sounds). It works offline in any browser, even after
  the school year is over.

## Features

| Area | What you can do |
|---|---|
| Creating | text (5 fonts, colours, size, background), photos, camera, drawing with eraser, sound recording, around 900 stickers in ten groups (look the same everywhere), shapes, page colours |
| Handling | drag, resize, rotate (with snapping), layers, duplicate, undo, autosave, big buttons for small hands |
| Formats | portrait, square, landscape |
| Reading | turn pages with buttons, swiping or arrow keys, full screen, tap to play sounds |
| Sharing | two buttons on your own book: **with the teacher** (appears for them under “Children’s books” in the “Shared” group) or **with the class** (all children can read it) |
| Fox helper | Rua the fox (a Viking fox from a Norwegian fjord village whom the Irish called “the red one”), animated in the bottom right, greets you by name, praises you after saving and gives tips suited to your role and page; in the editor he reacts to what you do, when reading he keeps you company while you turn the pages; on his own he speaks at most every 3 minutes; “Put to sleep” leaves him sleeping until you tap him; on the login page he promotes free software (with a link to `SOURCE_URL`); respects “reduce motion”. Admins can switch him off centrally, everyone can switch him off for themselves in their account |
| Summer holiday mode | admins set a date; at 3 a.m. on that day all student accounts are permanently deleted together with their books, photos, recordings and log entries — with a backup beforehand and a notice to the children three weeks in advance |
| Class book | the whole class writes in one book together; changes by others appear after a few seconds, simultaneous edits to the same page are merged, and you can see who is working where |
| Classes and subjects | admins create classes and subjects and set for each class which subjects it has and who teaches them; a class can have several teachers, a teacher several classes with different subjects. Children see their books sorted by subject, create them in a subject and move them; all teachers of a class see all its books, with a subject filter (own subjects first) |
| Templates | the teacher provides a book, each child creates their own version; “Children's versions” overview showing “started” and “handed in” |
| Change log | per book: who changed what and when (text, pictures, pages, title, sharing, hand-in) — for teachers and admins only |
| Output | print / PDF (A4 or square), file to take home |
| Users | first and last name, username, class; create whole class lists at once |
| Login cards | optional: additionally store the password encrypted so cards can be reprinted at any time |
| School directory | optional under **Settings → 9**: teachers and admins sign in with their school account (LDAP bind, no password in BilDaBooky); role from groups (teachers, admins), account created at first sign-in, “Test connection”; own accounts keep working |
| Login sessions | children can only log in when a teacher of their class has opened login (60/120/180 minutes or up to 7 days, then until 5 pm), with password **and** a 6-digit session code — for children this replaces two-factor login. All teachers of the class see the reason, the code and who is logged in, and can end the session early with a reason (children are logged out). History kept for 60 days. Admins can switch it off |
| Security | two-factor (TOTP) with recovery codes for teachers and admins — per group mandatory, optional or off, school directory groups with mandatory, exception per person; protection against password guessing |
| Languages | German, English, French, Spanish. The language is detected from the browser and can be switched. Anything missing is shown in German. |
| Branding | admins set the school name, logo, colours (accent, header, background), footer text and domain directly in the interface |
| Imprint and privacy policy | can be switched on individually, as your own text or a link to the school website; reachable without logging in, linked on the login page and in the footer; with a template for the privacy policy (written for German schools) |
| Help | quick guide, system, licence, latest errors of the past 30 days (admins, with “Clear log”) and changelog under “Help” in the footer |
| Operations | Docker, SQLite, nightly backup (database + media as ZIP) |

## Roles

| Role | May |
|---|---|
| **Admin** | a tick on a teacher (“Also admin”) — administration: create classes and subjects, manage teachers and admins, branding, domain and licence, download backups. **No** access to other people’s books — except in classes the admin teaches (then like a teacher) |
| **Leiter** (leader, i.e. teacher) | leads one or more classes: adds students there, resets passwords, prints login cards; creates class books and templates; sees and edits **all** books of their class; reads the change log |
| **Ersteller** (creator, i.e. student) | create, share and take home their own books; write in class books; create their own version from templates and hand it in; read books shared with the class |

### Who sees which book?

| Book | Child (owner) | other children in the class | teacher of the class |
|---|---|---|---|
| own book, not shared | edit | — | view, help, change log |
| own book, “with the teacher” | edit | — | as above, in the child’s “Shared” group |
| own book, “with the class” | edit | read | as above, in the child’s “Shared” group |
| class book | — | everyone edits | edit, delete/reorder pages, change log |
| template | — | view, start own version | edit, versions, change log |
| version from a template | edit, hand in | — | always visible, help, change log, give a handed-in version back with a message |

Admins don’t see other people’s books. If an admin also teaches, assign them to classes and subjects like a teacher; they then see exactly the books of those classes. Note: the backup technically contains all data — whoever may download it (admins) can access everything.

## Guides

- [Installing with Docker](installation/en/docker.md) — Linux server, mini PC, Docker Desktop, HTTPS with Caddy
- [Installing on Synology (Container Manager) and QNAP (Container Station)](installation/en/synology.md) — incl. HTTPS via the DSM reverse proxy
- [Operations](installation/en/operations.md) — updates, backups, restoring, emergencies

## Quick start

You need Docker with Compose.

```bash
git clone https://github.com/steffenmai-maidigital/bildabooky.git
cd bildabooky
mkdir -p backups
docker compose up -d --build
```

BilDaBooky is then available at <http://localhost:7290>.
First login as `admin`. The first admin password is **generated randomly** on the first start and
printed in the container log (`docker compose logs app | grep -A1 "Erstes Admin"`
or Container Manager → Project → Log). If you prefer your own, set it as
`ADMIN_PASSWORD` in the Compose file beforehand. The password must be changed at the first login.

**To try it out**, set `DEMO_MODE: "true"` in `docker-compose.yml`. This
creates two teachers (`sommer` / `sommer123` and `winter` / `winter123`),
class 2b with five subjects, two children (`mia` / `mia`, `ben` / `ben`) and
sample books. For the children to log in, a teacher opens login for the class
under “Classes → 🔓 Login” and gives them the session code.

### Synology (Container Manager)

Step by step in [installation/en/synology.md](installation/en/synology.md).
**Important:** browsers only allow camera and microphone over **HTTPS**; the
guide shows how to do this with the DSM reverse proxy.

### School branding

Under **🎨 Settings** (admins only):

1. **School**: name and an optional footer text (e.g. the address).
2. **Domain** (optional): the address everyone uses to reach BilDaBooky,
   e.g. `https://books.my-school.org`. It is printed on the login cards and
   BilDaBooky builds all links with it. See
   [Entering the domain](#entering-the-domain) below.
3. **Colours**: accent (buttons), header, background, with live preview.
   The text colour on top is chosen light or dark automatically so that
   everything stays readable.
4. **Licence**: licensee and licence key (see below).
5. **Logo**: PNG, JPG, WebP or SVG (max. 2 MB). It appears in the header, on
   the login page and as the browser icon.

These settings take precedence over `SCHOOL_NAME` and `APP_URL` from the
Compose file.

### Entering the domain

BilDaBooky builds **every** address with the domain you enter: the links on
the login cards, the redirect after logging in and the addresses of each
page's stylesheets, scripts and pictures.

**So only enter it once the address already works.** The safest order:

1. Set up the reverse proxy and certificate (see the guides).
2. Open BilDaBooky via the new address, e.g. `https://books.my-school.org`.
3. Only then enter the address under **Settings → Domain**.

**What happens if the address doesn't work yet?** Pages then load their
styling from an address that doesn't respond, and logging in redirects
there — you can no longer reach the settings to fix it. That's why
BilDaBooky asks for confirmation when you enter a domain you are **not**
currently using (“Yes, the address is correct …”).

**Emergency exit** if it happened anyway: this command removes the domain
you entered; afterwards `APP_URL` from the Compose file (or the address you
use to open BilDaBooky) applies again.

```bash
sudo docker exec bildabooky php artisan bildabooky:clear-domain
```

On Synology this also works without SSH: Container Manager → container
`bildabooky` → **Terminal** tab → start a session → enter
`php artisan bildabooky:clear-domain`.

If the field is left empty, `APP_URL` from the Compose file simply applies.

### Summer holiday mode

Under **Settings → 6 · Summer holiday mode** (admins only):

- switch it on and choose the **date of execution** (tomorrow at the
  earliest); the deletion runs at 3 a.m. on that day
- **all student accounts** are deleted together with their books (including
  versions from templates), photos, recordings and log entries; teachers and
  admins remain
- options: also delete class books and templates (default: yes), delete the
  classes themselves (default: no), repeat every year on the same day
- switching it on requires a confirmation; a preview shows what would be
  deleted
- three weeks before, everyone sees a notice — children are asked to take
  their books home as files
- immediately before, a backup `…_vor-sommerferien.zip` is created in the
  `backups` folder (deleted after `BACKUP_KEEP_DAYS` like every backup)

It is run by the scheduler container; if none is running, BilDaBooky checks
at most once an hour after a web request. Manually:

```bash
docker exec bildabooky php artisan bildabooky:summer-holiday --preview   # preview
docker exec -it bildabooky php artisan bildabooky:summer-holiday --now  # run now
```

### Editions and licence

| Edition | What |
|---|---|
| **Community Edition** | the open-source version. Complete, without restrictions and without expiry. |
| **Supported Edition** | the same software with a maintained support licence (Standard or Enterprise). |

The licence is **for information only and has no functional effect**.
Admins enter it under **Settings → 4 · Licence**: the licensee and a
20-character key. It is shown in the footer under **Help**. Expired support
is shown in red there; the application keeps working without restrictions.

Licence key schema (20 characters):

| Positions | Meaning | Example |
|---|---|---|
| 1 | `1` = Standard licence, `2` = Enterprise licence | `1` |
| 2–9 | customer number (8 characters, letters and digits) | `GSPARK01` |
| 10–15 | end of support as `YYMMDD` | `270731` |
| 16–20 | number of licences (5 digits, `99999` = unlimited) | `00500` |

Example: `1GSPARK0127073100500`. Only keys that match the schema are saved.

### Settings (environment variables)

| Variable | Default | Meaning |
|---|---|---|
| `SCHOOL_NAME` | Unsere Grundschule | shown in the header and on the login page |
| `APP_URL` | http://localhost:7290 | public address (for login cards). If it starts with `https://`, BilDaBooky builds all links with this address and https — needed behind proxy chains that report “http” internally (e.g. an upstream proxy in front of Nginx Proxy Manager). The domain in the settings takes precedence |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | admin / *(empty = random, printed in the log)* | first login, only on the very first start |
| `LDAP_HOST`, `LDAP_PORT`, `LDAP_SSL`, `LDAP_BASE_DN`, `LDAP_USERNAME`, `LDAP_PASSWORD`, … | *(empty)* | optional initial values for the school directory; everything is maintained under **Settings → 9** (see `.env.example`) |
| `REQUIRE_2FA_ROLES` | *(empty)* | initial value: e.g. `admin,teacher` sets the two-factor level of these groups to mandatory; afterwards maintained under **Settings → 8** (never for children) |
| `DEMO_MODE` | false | creates sample data |
| `SOURCE_URL` | *(empty)* | link to the source code in the footer (see licence) |
| `COMMUNITY_URL` | = `SOURCE_URL` | additional page for questions, shown under “Help → Support” |
| `GITHUB_URL` | GitHub project | source code and bug reports, shown under “Help → Support” (empty = hide) |
| `PROJECT_URL` | `https://www.mai-digital.de` | project page, shown under “Help → Support” (empty = hide) |
| `BACKUP_KEEP_DAYS` | 14 | how long nightly backups are kept |
| `LOG_DAILY_DAYS` | 30 | how long the daily logs are kept |
| `FRESH_DB` | 0 | `1` deletes the database **and** media on start. Set it back to `0` immediately afterwards. |
| `TRUSTED_PROXIES` | internal networks | reverse proxies whose information (real IP, HTTPS) is trusted; `*` = trust everyone (not recommended) |

The `APP_KEY` is generated on the first start and stored in the data volume
(`/data/.app_key`). This keeps logins, two-factor secrets and login cards
valid even after the container is recreated. **This file is part of the
backup.** Without it, two-factor and stored cards can no longer be
decrypted.

### Data and backups

- `app-data` (volume): database `bildabooky.sqlite`, media under `medien/`, `.app_key`
- `./backups`: a ZIP with database and media every night at 2:30 a.m.
- Admins can download a backup at any time under **Classes → Download backup**.

Restoring and moving: see [installation/en/operations.md](installation/en/operations.md).

## Versions and Docker images

Version numbers follow the scheme **YYMMDDNN**: the date plus a running
number for that day. `26101101` is the first version on 11 October 2026,
`26101102` the second one that day.

Every build produces the Docker image **`bildabooky:latest`**. A released
version is additionally tagged with its number, e.g.
**`bildabooky:26101101`**, so old images remain available as a way back
(see [Operations](installation/en/operations.md#going-back-to-an-older-version)).

For every version, GitHub also builds the image for Intel/AMD and ARM and
publishes it as `ghcr.io/steffenmai-maidigital/bildabooky:<version>` and
`:latest`. As long as the repository is private, the package is private too.

New versions are published by the maintainers; what changes in each version is
listed in the [CHANGELOG](CHANGELOG.md).

## Development

```bash
docker build -t bildabooky:dev .
docker run --rm -p 7290:80 -e DEMO_MODE=true -e APP_DEBUG=true bildabooky:dev
```

Structure:

```
app/Models          Book, Page, Media, SchoolClass, User (roles, permissions, 2FA)
app/Http/Controllers  books, pages, media, users, classes, account, login
app/Support         Totp (RFC 6238), Backup, Lang, Branding, License
public/js/book.js   page rendering (editor, reader, print, file to take home)
public/js/editor.js editor
public/js/reader.js reader
lang/*.json         translations (the German texts are the keys)
```

No build step: no Node and no npm. CSS and JavaScript live directly in
`public/`. Identifiers in the code are English (see [docs/RENAMING.md](docs/RENAMING.md)); comments are bilingual (German and English).

### Translating

All interface texts are written in German and serve as keys. For a new
language:

1. Copy `lang/en.json` to `lang/xx.json` and translate the values.
2. Add it under `sprachen` in `config/bildabooky.php`.
3. Keep placeholders such as `:name` unchanged. Before each version the
   maintainers check that everything is translated.

Missing texts automatically appear in German.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Please don't report security issues
publicly, see [SECURITY.md](SECURITY.md).

## Licence

[GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0-or-later).

You may use, modify and share BilDaBooky freely. Anyone who runs a
**modified** version as a web service must offer its users the source code
of that version. That's what `SOURCE_URL` is for: the link appears in the
footer.

**Stickers and icons:** [Twemoji](https://github.com/jdecked/twemoji)
© Twitter, Inc and other contributors, licensed under
[CC-BY 4.0](https://creativecommons.org/licenses/by/4.0/)
(`public/img/emoji/`, see the `LIZENZ.txt` there).
