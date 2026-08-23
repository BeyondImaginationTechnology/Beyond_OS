# AWS EC2 deployment

This runs the existing token-protected Remotion bridge on one Ubuntu EC2
instance behind Nginx and HTTPS. Imported artifacts and rendered MP4s stay on
the instance; no S3 or Lambda permission is required for the first deployment.

## 1. Launch the instance

- Ubuntu Server 24.04 LTS, `x86_64`.
- The existing `t3.small` can be used for setup and a short smoke render, but its
  2 GiB RAM is tight for Chromium. Resize to `t3.large` (8 GiB RAM) if a render
  is killed, stalls, or needs to run alongside other services.
- Use at least a 40 GiB gp3 encrypted EBS volume.
- Give the instance an Elastic IP.
- Security group inbound rules: TCP 22 from the administrator's IP only, TCP 80
  and 443 from the internet. Do not expose port 4317.
- Create an A record such as `render.beyondimagination.co.technology` pointing
  to the Elastic IP.

## 2. Install the host runtime

```bash
sudo apt update
sudo apt install -y nginx certbot python3-certbot-nginx unzip curl ca-certificates \
  fonts-liberation libasound2t64 libatk-bridge2.0-0 libatk1.0-0 libcups2 \
  libdbus-1-3 libdrm2 libgbm1 libgtk-3-0 libnspr4 libnss3 \
  libx11-xcb1 libxcomposite1 libxdamage1 libxfixes3 libxkbcommon0 \
  libxrandr2 xdg-utils
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
sudo corepack enable
sudo useradd --system --home /var/lib/beyond-remotion \
  --create-home --shell /usr/sbin/nologin beyond-remotion
sudo mkdir -p /opt/beyond-remotion
```

Upload `beyond-remotion-aws.zip`, then install it:

```bash
sudo unzip -o beyond-remotion-aws.zip -d /opt/beyond-remotion
cd /opt/beyond-remotion
sudo corepack pnpm install --prod --frozen-lockfile=false
sudo chown -R beyond-remotion:beyond-remotion /opt/beyond-remotion
```

## 3. Configure the bridge

Generate a token with `openssl rand -hex 32`. Create
`/etc/beyond-remotion.env` owned by root and mode `600`:

```dotenv
BEYOND_STUDIO_REMOTION_HOST=127.0.0.1
BEYOND_STUDIO_REMOTION_PORT=4317
BEYOND_STUDIO_REMOTION_TOKEN=replace-with-the-generated-token
BEYOND_STUDIO_REMOTION_ORIGINS=https://beyondimagination.co.technology
```

Install the included service and Nginx configuration:

```bash
sudo cp deploy/beyond-remotion.service /etc/systemd/system/
sudo cp deploy/nginx.conf /etc/nginx/sites-available/beyond-remotion
sudo ln -s /etc/nginx/sites-available/beyond-remotion \
  /etc/nginx/sites-enabled/beyond-remotion
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl daemon-reload
sudo systemctl enable --now beyond-remotion nginx
sudo certbot --nginx -d render.beyondimagination.co.technology
```

Set the web application's private configuration (not a committed file):

```php
'remotion' => [
    'bridge_url' => 'https://render.beyondimagination.co.technology',
    'bridge_token' => 'the-same-generated-token',
],
```

## 4. Smoke test

From an administrator machine:

```bash
curl -fsS https://render.beyondimagination.co.technology/api/health \
  -H 'Authorization: Bearer THE_TOKEN'
```

The response must contain `"ok":true` and `"remotionReady":true`. Then open
Beyond Studio → Video → Remotion renderer, import a trusted small Remotion ZIP,
render it, and download the MP4.

Useful host checks:

```bash
sudo systemctl status beyond-remotion --no-pager
sudo journalctl -u beyond-remotion -n 100 --no-pager
sudo nginx -t
curl -fsS http://127.0.0.1:4317/api/health \
  -H 'Authorization: Bearer THE_TOKEN'
```

## Operations

The bridge workspace is `/opt/beyond-remotion/workspace`. Schedule deletion of
old `imports/` and `outputs/` files after the desired retention period, and
monitor EBS free space. Rotate the bearer token by updating both the environment
file and the web application configuration, then restart the service.
