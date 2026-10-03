# Beyond Webs App v0.0.1

This folder is the document root for `hosting.beyondimagination.co.technology`.

Beyond Webs v0.0.1 is the web VPS request app. Every offered session is on a machine with BIT OS installed. Signed-in users can choose a BIT OS flavour, proposed machine size, and Linux work mode (developer tools, creator tools, or native Linux games), save or update one request in the shared Beyond ID database, and view its status in their account. Saving a request does not provision a machine, start usage metering, or charge the user. Rates and availability must be confirmed before a future provisioning flow is enabled.

The request API is `api/session.php`. It accepts authenticated GET and authenticated JSON POST with a session CSRF token. The database table is created by the matching MySQL or SQLite `20260930_01_beyond_webs_requests` migration in `beyond-id/database/migrations/`; the `20261003_01_beyond_webs_work_mode` migration adds the selected work mode. If automatic migrations are disabled, apply both migrations before deploying this app. Requests stay in `requested` status until a separate provisioning integration is added. Launch, Build, and Power are initial machine profile targets shown on the page; requests do not guarantee capacity, availability, or an hourly price.

Suggested Linux creator apps are Blender, Krita, GIMP, and Kdenlive. Suggested native Linux games are OpenTTD with its open baseset, 0 A.D., and The Battle for Wesnoth. Package each app with its license and required notices; audit add-ons and user contributed game content separately. Google Cloud Compute Engine GPUs can host full BIT OS VMs where GPU capacity and quota are available. The app currently records requests only and has no Google Cloud provisioning integration.

## Machine profiles and browser access

| Profile | vCPU | RAM | Capped SSD | GPU |
| --- | ---: | ---: | ---: | --- |
| Launch | 4 | 16 GB | 128 GB | None |
| Build | 8 | 32 GB | 256 GB | Optional, subject to capacity |
| Power | 16 | 64 GB | 512 GB | Required, subject to quota and region |

These are initial product targets. Select compatible Compute Engine machine types only after checking regional capacity, GPU quota, disk type, and current price. Do not silently substitute a different profile. Keep each VM disk at or below the selected cap.

The browser desktop uses noVNC over WebSocket. VNC and websockify must bind to loopback on the session VM. Publish the viewer only through an authenticated HTTPS session gateway that checks the signed-in Beyond ID user against the assigned VM/session, and revoke access when the session stops. Never send raw VNC credentials to the browser, expose TCP 5900/6080 publicly, or place a reusable direct noVNC URL in the request record. The account page currently shows a pending-provisioning message; no live desktop link is issued yet.

The existing `beyond-os-desktop/cloud/` harness validates a Core image inside QEMU on a host VM. It is a prototype path, not the production multi-user broker. Production profiles should provision one isolated BIT OS VM per active seat, then route each authorized user's noVNC WebSocket through the gateway.

## Host configuration

1. Create an HTTPS subdomain for `hosting.beyondimagination.co.technology` in the hosting control panel.
2. Map its document root to the deployed `beyond-webs/` folder.
3. The production Beyond host shares sessions automatically. Set this optional PHP environment variable on the primary Beyond ID host when an explicit deployment value is preferred:

   ```text
   BEYOND_WEBS_ORIGIN=https://hosting.beyondimagination.co.technology
   ```

4. Confirm that `https://hosting.beyondimagination.co.technology/` loads and that **Continue with Beyond ID** returns to the same subdomain after sign-in.

Beyond ID defaults to this exact HTTPS host and also restricts an explicit `BEYOND_WEBS_ORIGIN` to it. This prevents sign-in returns from going to another external site.
