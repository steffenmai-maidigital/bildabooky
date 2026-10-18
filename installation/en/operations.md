# Guide: Operations — updates, backups, restoring, emergencies

*Deutsche Fassung: [betrieb.md](../betrieb.md)*

Applies to plain Docker as well as Synology/QNAP. On a NAS, run the commands
via SSH with `sudo` in front. The examples assume the Compose project is
called `bildabooky` (the folder name) and lives in the folder `bildabooky`.

The command names are German (`bildabooky:backup` = backup); the table at
the end explains each of them.

## What is stored where?

| Location | Contents |
|---|---|
| volume `bildabooky_app-data` (`/data`) | database `bildabooky.sqlite`, photos/drawings/sound under `medien/`, school logo under `marke/`, key `.app_key` |
| volume `bildabooky_app-storage` | sessions, cache, logs (30 days) |
| folder `./backups` | nightly backups as ZIP files |

**`.app_key` matters:** the two-factor secrets and the stored login cards
are encrypted with it. It is included in every backup, so keep the
`backups` folder away from prying eyes (share permissions for admins only).

## Updates

There is deliberately no `composer.lock`: every rebuild fetches the current
versions within the ranges allowed in `composer.json`.

**Docker:**

```bash
cd bildabooky
git pull                     # or unpack a new ZIP, keeping your own docker-compose.yml
docker compose up -d --build
```

**Synology Container Manager:** stop the project → unpack the new files over
the old ones (back up your `docker-compose.yml` first and copy it back
afterwards) → project → **Action → Clean**, then **Build**.

### What happens on start

On every start the app container runs `php artisan app:upgrade`:

| Step | Effect |
|---|---|
| Version check | The version stored in the database is compared with the image version. If the database is newer (**downgrade**), the container does not start – nothing is changed. |
| Backup | Before any change a copy of the database is stored in `/data/backups/` (the 10 newest are kept). |
| Migrations | New tables and columns are created. |
| Record | **Help → System** then shows the new version and build number. |

### Versions

Version numbers follow the scheme **YYMMDDNN** (date plus a running number
for that day, e.g. `26101101`). Every build is called `bildabooky:latest`;
published versions also carry their number. To list
the images you have:

```bash
docker image ls bildabooky
```

### Going back to an older version

Because BilDaBooky refuses downgrades, going back also needs the database
from before the update. Media and the key stay as they are.

1. Stop the containers: `docker compose down`
2. Find and restore the database backup taken before the update:

   ```bash
   docker run --rm -v bildabooky_app-data:/data alpine ls -1 /data/backups
   docker run --rm -v bildabooky_app-data:/data alpine sh -c \
     'cp /data/backups/FILE.sqlite /data/bildabooky.sqlite && rm -f /data/bildabooky.sqlite-wal /data/bildabooky.sqlite-shm'
   ```

3. In `docker-compose.yml`, comment out the line `build: .` for **both**
   services (`# build: .`) and put the old number in `image:`, e.g.
   `image: bildabooky:26101101`. Without commenting it out, the next start
   would build the new version again.
4. Run `docker compose up -d`, or rebuild the project in Container Manager.

So don't clean up old images too eagerly (`docker image prune -a` removes
them).

## Backups

- **Automatic:** every night at 2:30 a.m. by the container
  `bildabooky-scheduler` into `./backups`
  (`bildabooky_YYYY-MM-DD_hhmmss.zip`). Backups are kept for
  `BACKUP_KEEP_DAYS` days (default 14).
- **Right away, in the browser:** as an admin under **Classes → Download
  backup**.
- **Right away, by command:**

  ```bash
  docker exec bildabooky php artisan bildabooky:backup
  ```

- **Keep an extra copy elsewhere:** regularly copy the `backups` folder to a
  second device, on Synology e.g. with **Hyper Backup**.

A backup contains `bildabooky.sqlite` (a consistent copy, even while
running), the folders `medien/` (photos, drawings, sound) and `marke/`
(school logo), and `.app_key`.

## Restoring

Tested with the Compose project `bildabooky`. Replace `FILE.zip` with the
name of the backup.

```bash
cd bildabooky
docker compose stop

docker run --rm -v bildabooky_app-data:/data -v "$PWD/backups:/backups:ro" alpine sh -c "
  set -e; mkdir /tmp/r && cd /tmp/r && unzip -q /backups/FILE.zip
  rm -f /data/bildabooky.sqlite /data/bildabooky.sqlite-wal /data/bildabooky.sqlite-shm
  cp bildabooky.sqlite /data/ && rm -rf /data/medien && cp -r medien /data/medien
  cp .app_key /data/.app_key
  if [ -d marke ]; then rm -rf /data/marke && cp -r marke /data/marke; fi
  echo restored"

docker compose start
```

BilDaBooky fixes the file permissions itself on start.

**Moving to another server:** set up BilDaBooky there as described in the
installation guide and start it once, then restore as shown above.

## Emergencies

### Emergency: admin password

Another admin can reset the password under **Users → Edit**. If there is
none:

```bash
docker exec bildabooky php artisan bildabooky:admin-password admin
# Lost the phone with the authenticator app? Reset two-factor at the same time:
docker exec bildabooky php artisan bildabooky:admin-password admin --disable-2fa
```

The command prints a new starting password that has to be changed at the
next login.

### Wrong domain entered

```bash
docker exec bildabooky php artisan bildabooky:clear-domain
```

### Troubleshooting

- As an admin: **Help → Latest errors** shows the errors of the last
  30 days (with a button to clear the log).
- In detail: `docker compose logs app`, or in Container Manager
  **Project → Log**.
- For a precise error message in the browser, briefly set
  `APP_DEBUG: "true"`, rebuild and then **always** set it back to `"false"`.

### Resetting everything

`FRESH_DB: "1"` in `docker-compose.yml` deletes the **database and all
media** on the next start. Set it back to `"0"` immediately afterwards,
otherwise it happens again on every start.

## Recurring tasks

| When | What |
|---|---|
| start of the school year | create or rename classes, assign teachers, add children and print login cards |
| end of the school year | check the summer holiday mode (**Settings → 6**): date, options, preview. Three weeks before, BilDaBooky reminds the children to take their books home. |
| monthly | look at **Help → Latest errors** and into the `backups` folder |
| before updates | download a backup |

### Useful commands

| Command | Purpose |
|---|---|
| `php artisan bildabooky:backup` | create a backup right away |
| `php artisan bildabooky:summer-holiday --preview` | preview what the summer holiday mode would delete |
| `php artisan bildabooky:summer-holiday --now` | run the summer holiday mode now (asks for confirmation) |
| `php artisan bildabooky:admin-password admin` | emergency password for an admin (`--disable-2fa` also resets two-factor) |
| `php artisan bildabooky:clear-domain` | remove the domain entered in the settings |

Prefix each with `docker exec bildabooky` (or `docker exec -it` for the
confirmation prompt of `--now`).
