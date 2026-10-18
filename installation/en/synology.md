# Guide: Installing on Synology (Container Manager) and QNAP (Container Station)

*Deutsche Fassung: [synology.md](../synology.md)*

> **Names:** On **Synology** the Docker interface is called **Container
> Manager** (DSM 7.2 and newer; on older DSM versions the package was called
> “Docker”). **Container Station** is the equivalent on **QNAP**. This guide
> covers both; QNAP follows further down.

## Requirements

- Synology NAS with **DSM 7.2** or newer
- the **Container Manager** package from the Package Center
- at least 2 GB of RAM in the NAS (4 GB or more runs comfortably)
- Intel/AMD models (e.g. the Plus series) and ARM models are supported; on
  ARM the first build takes considerably longer
- internet access for the NAS **while building** (Laravel is downloaded);
  BilDaBooky does not need the internet while running

## 1. Copy the files to the NAS

1. On GitHub choose **Code → Download ZIP** (or use the ZIP you were given).
2. In **File Station**, create a folder `bildabooky` inside the shared folder
   `docker`.
3. Upload the ZIP there, right-click → **Extract → Extract here**. Afterwards
   `Dockerfile`, `docker-compose.yml`, `app/` etc. must be **directly** in
   `docker/bildabooky/` (not in a subfolder such as `bildabooky-main/`; if
   so, move the contents up one level).
4. Create an empty folder **`backups`** inside `docker/bildabooky`. The
   nightly backups end up there.

## 2. Adjust the settings

Open `docker-compose.yml` with DSM's **Text Editor** package, or edit the
YAML later in Container Manager under the project. Change at least:

- `SCHOOL_NAME: "Park Primary School"`
- `APP_URL: "https://books.my-school.org"` (or for now
  `http://<NAS-IP>:7290`)
- port `"7290:80"`: choose a free port on the left if 7290 is taken

All values are explained in the [README](../../README.en.md). School name,
logo, colours and domain can also be changed conveniently in the admin area
later.

## 3. Create the project

1. Open **Container Manager** → **Project** → **Create**.
2. **Project name:** `bildabooky`
3. **Path:** select the folder `docker/bildabooky`.
4. **Source:** “Use existing docker-compose.yml” (it is detected).
5. Skip the **Web portal** step (we set this up in step 5 as a reverse proxy
   with HTTPS).
6. **Done**. The first start builds the image and takes 5–20 minutes
   depending on the NAS. Progress: project `bildabooky` → **Log**.

It is ready once both containers **`bildabooky`** and
**`bildabooky-scheduler`** are running (green). Then open
`http://<NAS-IP>:7290` in the browser.

## 4. First steps

1. The first admin password is in the log: Container Manager → project
   `bildabooky` → **Log** → line “Erstes Admin-Passwort für „admin““. Log in
   as `admin` with it and set your own password. (If you prefer to choose
   one yourself, set it as `ADMIN_PASSWORD` in `docker-compose.yml` before
   the first start.)
2. Set up two-factor login under **Account** (recommended).
3. Enter the school name, logo and colours under **Settings**. The interface
   language can be switched at the top right (🌐).
4. Create the classes under **Classes** and the teachers under **Users**
   (role *Leader (teacher)*, assign their classes). The teachers add their
   own students and print the login cards.

To try it out first, set `DEMO_MODE: "true"`: you then get a teacher
(`sommer` / `sommer123`), a second teacher (`winter` / `winter123`), two children (`mia` / `mia`, `ben` / `ben`) and
sample books.

## 5. HTTPS via the DSM reverse proxy (for camera and microphone)

Browsers only allow camera and microphone over **HTTPS**. DSM has everything
you need:

1. **Certificate:** Control Panel → **Security → Certificate** → **Add** →
   “Get a certificate from Let's Encrypt” for `books.my-school.org`. (The
   name has to point to the NAS in DNS and port 80 must be reachable.)
2. **Reverse proxy:** Control Panel → **Login Portal → Advanced → Reverse
   Proxy** → **Create**:
   - Source: protocol **HTTPS**, hostname `books.my-school.org`, port
     **443**
   - Destination: protocol **HTTP**, hostname `localhost`, port **7290**
   - **Custom Header** tab → **Create → WebSocket** (does no harm) and
     additionally `X-Forwarded-Proto` = `https`
3. Under Control Panel → Security → Certificate → **Settings**, assign the
   Let's Encrypt certificate to the new entry `books.my-school.org`.
4. Now open BilDaBooky via `https://books.my-school.org`. If that works,
   enter the address **there** under **Settings → Domain** (or set `APP_URL`
   in `docker-compose.yml` to it and rebuild the project). Only enter it
   once the address works — otherwise you lock yourself out; see the
   [README under “Entering the domain”](../../README.en.md#entering-the-domain).

Only on the school network, without a public domain? A certificate for an
internal name from your own certificate authority works too; the devices
then have to trust it.

In detail – including Nginx Proxy Manager and nginx: [reverse-proxy.md](reverse-proxy.md).

## 6. Port forwarding and firewall

- Only **443** (HTTPS), plus **80** for Let's Encrypt, needs to be reachable
  from outside. Do **not** forward port **7290** on the router.
- In the DSM firewall, open port 7290 for the local network at most.

## Data, updates, backups

- The database, photos, recordings and the key `.app_key` live in the Docker
  volume **`bildabooky_app-data`**; sessions and logs in
  **`bildabooky_app-storage`**. Both survive restarts and updates.
- Backups are stored in `docker/bildabooky/backups` and can additionally be
  copied to an external drive with **Hyper Backup**.
- **Update:** **stop** the project → unpack the new files over the old ones
  in `docker/bildabooky` (**back up your customised `docker-compose.yml`
  first and copy it back afterwards!**) → project → **Action → Clean**, then
  **Build**. BilDaBooky applies database changes automatically on start.

More details: [operations.md](operations.md).

## Alternative: via SSH

Enable SSH (Control Panel → Terminal & SNMP), then:

```bash
ssh admin@<NAS-IP>
cd /volume1/docker/bildabooky
sudo docker compose up -d --build
sudo docker compose logs -f app
```

DSM 7.1 with the old “Docker” package has no projects. There SSH is the
easiest way (`sudo docker-compose up -d --build`, with a hyphen).

---

## QNAP Container Station

1. In **File Station**, unpack the ZIP to e.g. `/Container/bildabooky`
   (again with the files **directly** in the folder) and create a folder
   `backups` inside it.
2. Adjust `docker-compose.yml` as described above.
3. Open **Container Station** → **Applications** → **Create** → application
   name `bildabooky` → paste the contents of `docker-compose.yml`.
   - Because BilDaBooky is built on the first start, Container Station needs
     the folder as build context. If that doesn't work in the interface, SSH
     is the reliable way:

     ```bash
     ssh admin@<NAS-IP>
     cd /share/Container/bildabooky
     docker compose up -d --build
     ```
4. Then open `http://<NAS-IP>:7290` and continue with step 4 above.
5. **HTTPS:** in QTS/QuTS hero, under **Control Panel → Network & File
   Services → Reverse Proxy** (or **myQNAPcloud → Reverse Proxy**), create a
   rule from `https://books.my-school.org:443` to `http://localhost:7290`
   and assign a Let's Encrypt certificate.

## Common problems

| Problem | Solution |
|---|---|
| Project doesn't start, build fails | Check the log. Usually the internet was briefly unavailable while downloading packages: simply **Build** again. |
| “Server Error” in the browser | Set `APP_DEBUG: "true"`, rebuild the project, read the error, then set it back to `"false"`. As an admin, **Help → Latest errors** also shows the cause. |
| Camera/microphone don't work | The page isn't opened via HTTPS → step 5. |
| “Too many redirects” behind several proxies | Two places force HTTPS and redirect back and forth. Force HTTPS in one place only, e.g. switch off “Force SSL” in Nginx Proxy Manager. |
| Page without styling behind a proxy that reports “http” internally | Set `APP_URL: "https://your-address.org"` (with https) in `docker-compose.yml` or enter it under Settings → Domain — BilDaBooky then only builds https links. |
| A recording or photo won't upload | BilDaBooky accepts up to 20 MB per file; photos are already shrunk in the browser. If you run your own proxy in front, allow uploads of up to 30 MB there (nginx: `client_max_body_size 30m;`). |
| Old view after an update | Clear the browser cache or reload with Shift+Reload. |
| Locked out after entering a wrong domain (pages without styling, login leads nowhere) | Remove the domain again: `sudo docker exec bildabooky php artisan bildabooky:clear-domain` — or without SSH: Container Manager → container `bildabooky` → **Terminal** → `php artisan bildabooky:clear-domain` |
| Admin password forgotten | Ask a second admin to reset it under **Users**. If there isn't one: see [operations.md](operations.md#emergency-admin-password). |
