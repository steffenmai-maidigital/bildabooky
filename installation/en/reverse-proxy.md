# Running BilDaBooky behind a reverse proxy (HTTPS)

*Deutsche Fassung: [../reverse-proxy.md](../reverse-proxy.md)*

Inside the container BilDaBooky only speaks HTTP on port 80. In production a
**reverse proxy** accepts requests over HTTPS and passes them on. This guide
explains what matters and shows the setup for the common proxies.

## The three rules

1. **Set `APP_URL` to the public address** – with `https://` and without a
   trailing slash:

   ```yaml
   APP_URL: "https://buecher.meine-schule.de"
   ```

   Laravel builds every link to stylesheets, images and forms from it. If it
   still contains the internal address, the page arrives in the browser but
   without styling. If `APP_URL` starts with `https://`, BilDaBooky generates all
   links with https – even if the proxy reports "http" internally.

2. **Pass the headers on:** the proxy must send `Host`, `X-Forwarded-For` and
   above all **`X-Forwarded-Proto`** to BilDaBooky. Most proxies do so by default.

3. **Trust the proxy:** BilDaBooky only believes these headers from proxies listed
   in `TRUSTED_PROXIES`. The default is the internal networks
   (`127.0.0.1, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16`). If the proxy runs
   in the same Docker network or the school network, that is enough.
   Otherwise enter its address, e.g. `TRUSTED_PROXIES: "203.0.113.10"`.

After every change to the Compose file run `docker compose up -d` (Synology:
rebuild the project).

## Synology DSM

Control Panel → **Login Portal → Advanced → Reverse Proxy** → **Create**:

| Field | Value |
|---|---|
| Source: protocol / hostname / port | HTTPS / `buecher.meine-schule.de` / 443 |
| Destination: protocol / hostname / port | HTTP / `localhost` / 7290 |
| Custom Header | **Create → WebSocket** |

Assign the certificate under Control Panel → **Security → Certificate →
Settings**. Step by step: [synology.md](synology.md#5-https-via-the-dsm-reverse-proxy-for-camera-and-microphone).

## Nginx Proxy Manager

**Hosts → Proxy Hosts → Add Proxy Host:**

- *Domain Names:* `buecher.meine-schule.de`
- *Scheme:* `http`, *Forward Hostname / IP:* address of the server or NAS,
  *Forward Port:* `7290`
- switch on *Block Common Exploits*
- tab **SSL:** choose or request a certificate, switch on **Force SSL** and
  **HTTP/2**
- tab **Advanced** (for uploads): `client_max_body_size 30m;`

Nginx Proxy Manager sets the required headers itself.

## nginx

```nginx
server {
    listen 443 ssl http2;
    server_name buecher.meine-schule.de;

    ssl_certificate     /etc/letsencrypt/live/buecher.meine-schule.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/buecher.meine-schule.de/privkey.pem;

    client_max_body_size 30m;     # photos and sound recordings

    location / {
        proxy_pass http://127.0.0.1:7290;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host  $host;
        proxy_read_timeout 120s;
    }
}

server {
    listen 80;
    server_name buecher.meine-schule.de;
    return 301 https://$host$request_uri;
}
```

## Caddy

Caddy obtains the certificate itself. Next to `docker-compose.yml` create a
file called `Caddyfile`:

```
buecher.meine-schule.de {
    reverse_proxy app:80
}
```

and add this to `docker-compose.yml` under `services:`:

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

Also add `caddy-data:` under `volumes:`. Requirement: the name points to the
server via DNS and ports 80 and 443 are reachable.


## Checking it

1. Open the page at `https://buecher.meine-schule.de`.
2. **Right-click → View page source** and search for `app.css`. The link must
   start with `https://buecher.meine-schule.de/`.
3. If it shows `http://localhost:7290` or `http://…`: check `APP_URL`,
   restart the container and press **Ctrl+F5** in the browser.

## Common mistakes

| Mistake | Cause and solution |
|---|---|
| Page without styling | `APP_URL` does not match the address in the browser (rule 1). |
| "Too many redirects" | Two places force HTTPS or redirect back and forth, often an upstream proxy that forwards via http internally, with Nginx Proxy Manager behind it set to "Force SSL". Force HTTPS in one place only, e.g. switch off "Force SSL" in NPM. |
| Links point to `http://`, the browser reports "mixed content" | `X-Forwarded-Proto` is missing (rule 2) or the proxy is not in `TRUSTED_PROXIES` (rule 3). The simplest fix: set `APP_URL` with `https://`. |
| The log always shows the same client IP | The proxy is not in `TRUSTED_PROXIES`; the per-address login brake then hits everyone together. Add the proxy's address. |
| HTTP 413 when uploading | Increase `client_max_body_size` in the proxy (at least 30 MB). |
| Running in a sub-path (`https://my-school.example/bildabooky/`) | not supported – please use a separate subdomain. |

## BilDaBooky specifics

- **Camera and microphone** only work in the browser over **HTTPS** (or on
  `localhost`). Without a reverse proxy with a certificate, children cannot
  take photos or record sound.
- Instead of `APP_URL` the address can also be set under **Settings →
  Domain**. Only enter it once BilDaBooky is already reachable at that
  address – otherwise you lock yourself out. Way back in:
  `docker exec bildabooky php artisan bildabooky:clear-domain`. More in the
  [README under "Entering the domain"](../../README.en.md#entering-the-domain).
