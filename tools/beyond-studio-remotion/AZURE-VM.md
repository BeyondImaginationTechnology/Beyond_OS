# Azure VM deployment

This deployment keeps the Node renderer private on `127.0.0.1:4317`. Nginx is
the only public process, obtains HTTPS through Let's Encrypt, and forwards
requests to the renderer. The Beyond Studio admin page sends a bearer token with
every API request.

## 1. Create the VM in Azure Portal

From **Virtual machines**, choose **Create → Azure virtual machine** and use:

| Setting | Value |
| --- | --- |
| Resource group | `beyond-studio-renderer-rg` |
| VM name | `beyond-remotion-01` |
| Region | A nearby region where the eligible free size is available |
| Availability options | No infrastructure redundancy required |
| Security type | Standard |
| Image | Ubuntu Server 22.04 LTS, x64 Gen2 |
| Size | Standard_B1s (eligible free-account size; verify the price shown) |
| Authentication | SSH public key |
| Username | `azureuser` |
| Inbound ports | SSH (22), HTTP (80), HTTPS (443) only |
| OS disk | Standard SSD, smallest free-account-eligible option |

Enable auto-shutdown as a cost guardrail if the renderer does not need to be
available all day. Download the generated `.pem` key and keep it private.

## 2. Point a hostname at it

On the VM Overview page, copy the public IP. At the DNS provider for
`beyondimagination.co.technology`, create:

```text
Type: A
Name: render
Value: <AZURE_PUBLIC_IP>
TTL: Auto or 300
```

Wait until `nslookup render.beyondimagination.co.technology` returns that IP.

## 3. Upload the renderer

In Windows PowerShell, replace the key path and IP:

```powershell
scp -i "$HOME\Downloads\beyond-remotion.pem" -r "D:\Beyond_OS\tools\beyond-studio-remotion" azureuser@AZURE_PUBLIC_IP:/tmp/
ssh -i "$HOME\Downloads\beyond-remotion.pem" azureuser@AZURE_PUBLIC_IP
```

Run the remaining commands in the Ubuntu SSH session.

## 4. Install Node, Nginx, and the renderer

Install system packages:

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl ffmpeg nginx certbot python3-certbot-nginx xz-utils
```

Install Node.js 22 from the official binary distribution:

```bash
cd /tmp
curl -fsSLO https://nodejs.org/dist/latest-v22.x/node-v22.23.2-linux-x64.tar.xz
sudo tar -xJf node-v22.23.2-linux-x64.tar.xz -C /usr/local --strip-components=1
node --version
```

If that exact Node patch is no longer present, select the current v22 Linux x64
archive from `https://nodejs.org/dist/latest-v22.x/` and substitute its filename.

Install the app under `/opt`:

```bash
sudo useradd --system --create-home --home-dir /var/lib/beyond-remotion --shell /usr/sbin/nologin beyond-remotion
sudo mkdir -p /opt/beyond-remotion
sudo cp -a /tmp/beyond-studio-remotion/. /opt/beyond-remotion/
sudo chown -R beyond-remotion:beyond-remotion /opt/beyond-remotion /var/lib/beyond-remotion
cd /opt/beyond-remotion
sudo -u beyond-remotion npm install --omit=dev
```

The B1s has only 1 GiB of RAM. Add a 2 GiB swap file so Chromium is less likely
to be killed during a 1080p render:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## 5. Configure the secret and system service

Generate a token:

```bash
openssl rand -hex 32
```

Create `/etc/beyond-remotion.env` with the generated value:

```bash
sudo nano /etc/beyond-remotion.env
```

```dotenv
BEYOND_STUDIO_REMOTION_HOST=127.0.0.1
BEYOND_STUDIO_REMOTION_PORT=4317
BEYOND_STUDIO_REMOTION_TOKEN=PASTE_THE_GENERATED_TOKEN
BEYOND_STUDIO_REMOTION_ORIGINS=https://beyondimagination.co.technology
```

Protect it, install the included service, and start it:

```bash
sudo chmod 600 /etc/beyond-remotion.env
sudo cp /opt/beyond-remotion/deploy/beyond-remotion.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now beyond-remotion
sudo systemctl status beyond-remotion --no-pager
```

## 6. Put HTTPS in front

Install the included Nginx site, then obtain a certificate:

```bash
sudo cp /opt/beyond-remotion/deploy/nginx.conf /etc/nginx/sites-available/beyond-remotion
sudo ln -s /etc/nginx/sites-available/beyond-remotion /etc/nginx/sites-enabled/beyond-remotion
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d render.beyondimagination.co.technology
```

Test from the VM. The first call should return `401`; the second should return
the health JSON:

```bash
curl -i https://render.beyondimagination.co.technology/api/health
set -a; source /etc/beyond-remotion.env; set +a
curl -H "Authorization: Bearer $BEYOND_STUDIO_REMOTION_TOKEN" https://render.beyondimagination.co.technology/api/health
```

## 7. Connect Beyond Studio

In the protected Beyond live configuration, add the same token:

```php
'remotion' => [
    'bridge_url' => 'https://render.beyondimagination.co.technology',
    'bridge_token' => 'PASTE_THE_GENERATED_TOKEN',
],
```

Do not put the token in the Git repository. Reload **Beyond Studio → Video →
Remotion renderer**; the badge should change to **Render bridge online**.

## Operations

```bash
sudo systemctl restart beyond-remotion
sudo journalctl -u beyond-remotion -n 100 --no-pager
sudo systemctl status nginx --no-pager
```

Only one small VM process is intended. Imported artifacts and completed MP4s
remain on its local disk until manually cleaned. Treat uploaded ZIP/HTML files as
trusted code.
