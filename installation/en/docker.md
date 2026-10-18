# Guide: Installing with Docker

*Deutsche Fassung: [docker.md](../docker.md)*

This guide works on any machine running Docker: a Linux server on the school
network, a mini PC, or Docker Desktop on Mac and Windows for trying it out.
There is a separate guide for Synology and QNAP NAS devices:
[synology.md](synology.md).

## Requirements

- Docker 24 or newer with Docker Compose (`docker compose version`)
- 1 GB of free RAM, 2 GB of disk space plus room for photos and recordings
- Internet access **only while building** (Composer downloads Laravel).
  BilDaBooky does not need the internet while running.
- For camera and microphone: an address with **HTTPS** (see below)

## 1. Get the files

```bash
git clone https://github.com/steffenmai-maidigital/bildabooky.git
cd bildabooky
```

Without Git: on GitHub choose **Code → Download ZIP**, unpack it and change
into the folder.

## 2. Adjust the settings

All settings live in `docker-compose.yml`; you don't need a `.env` file. The
most important ones:

| Setting | Meaning |
|---|---|
| `SCHOOL_NAME` | name of the school (can also be changed later in the admin area) |
| `APP_URL` | address where BilDaBooky can be reached, e.g. `https://books.my-school.org` |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | first login (only on the very first start). Empty = random password in the log; it must be changed straight away |
| `DEMO_MODE` | `"true"` creates a sample class, teacher, children and books |
| `REQUIRE_2FA_ROLES` | e.g. `"admin"`: two-factor login is mandatory for admins |
| `SOURCE_URL` | link to the source code (AGPL), shown in the footer |
| port `"7290:80"` | put the port you want on the left, e.g. `"8080:80"` |

The full list is in the [README](../../README.en.md).

## 3. Start

```bash
mkdir -p backups
docker compose up -d --build
```

The first start builds the image and takes a few minutes. Follow the
progress with:

```bash
docker compose logs -f app
```

It is ready once `Admin angelegt: admin`, followed by the **first admin
password**, and `resuming normal operations`
appear. Then open <http://localhost:7290> (or `http://<server-IP>:7290`) in
the browser.

## 4. First steps

1. Log in as `admin` with the password from the log
   (`docker compose logs app | grep -A1 "Erstes Admin"`) and set your own
   password.
2. Set up two-factor login under **Account** (recommended).
3. Enter the school name, logo and colours under **Settings**. Only enter
   the **domain** once HTTPS is set up and you already open BilDaBooky via
   that address (step 5).
   The interface language can be switched at the top right (🌐).
4. Create the classes under **Classes**.
5. Create the teachers under **Users** (role *Leader (teacher)*, assign
   their classes). The teachers then add their own students and print the
   login cards.

## 5. Set up HTTPS (for camera and microphone)

Browsers only allow camera and microphone over HTTPS or on `localhost`. For
use in school, put a reverse proxy with a certificate in front. The easiest
option is [Caddy](https://caddyserver.com), which fetches the certificate
by itself.

Create a file called `Caddyfile` next to `docker-compose.yml`:

```
books.my-school.org {
    reverse_proxy app:80
}
```

and add this under `services:` in `docker-compose.yml`:

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

plus `caddy-data:` under `volumes:`. Then set `APP_URL` to the HTTPS address
and run `docker compose up -d`. This requires the name to point to the
server in DNS and ports 80/443 to be reachable.

Once the new address works, open BilDaBooky through it and only then enter
it under **Settings → Domain**. Why this order matters and how to get back
in after a mistake is explained in the
[README under “Entering the domain”](../../README.en.md#entering-the-domain).

If you already run a reverse proxy (nginx, Traefik, Apache), simply forward
the domain to `http://<server>:7290`. If the proxy isn't on the same Docker or
school network, add its address to `TRUSTED_PROXIES` (default: internal
networks only). Also allow uploads of up to 30 MB (`client_max_body_size 30m;`
in nginx).

In detail – including Nginx Proxy Manager and nginx: [reverse-proxy.md](reverse-proxy.md).

## 6. Running it

- Updates, backups and restoring: [operations.md](operations.md)
- The second container `bildabooky-scheduler` runs the nightly backup
  (2:30 a.m.) and the summer holiday mode (3:00 a.m.). It has to keep
  running.

## Try it without Compose

For a quick look a single container is enough (data is lost when the
container is removed):

```bash
docker build -t bildabooky .
docker run --rm -p 7290:80 -e DEMO_MODE=true bildabooky
```

Demo logins: teachers `sommer` / `sommer123` and `winter` / `winter123`, students `mia` / `mia` and
`ben` / `ben`. The admin password is printed in the log (`docker logs <container>`),
or set it yourself: `-e ADMIN_PASSWORD=…`.
