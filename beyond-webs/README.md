# Beyond Webs App v0.0.1

This folder is the document root for `hosting.beyondimagination.co.technology`.

Beyond Webs v0.0.1 is the web VPS request app. Every offered session is on a machine with BIT OS installed. Signed-in users can choose a BIT OS flavour, proposed machine size, and Linux work mode (developer tools, creator tools, or native Linux games), save or update one request in the shared Beyond ID database, and view its status in their account. Saving a request does not provision a machine, start usage metering, or charge the user. Rates and availability must be confirmed before a future provisioning flow is enabled.

The request API is `api/session.php`. It accepts authenticated GET and authenticated JSON POST with a session CSRF token. The database table is created by the matching MySQL or SQLite migrations in `beyond-id/database/migrations/`; later migrations add work mode and cloud session state. If automatic migrations are disabled, apply every Beyond Webs migration before deploying this app. Launch, Build, and Power are initial machine profile targets shown on the page; requests do not guarantee capacity, availability, or an hourly price.

Suggested Linux creator apps are Blender, Krita, GIMP, and Kdenlive. Suggested native Linux games are OpenTTD with its open baseset, 0 A.D., and The Battle for Wesnoth. Package each app with its license and required notices; audit add-ons and user contributed game content separately. Google Cloud Compute Engine GPUs can host full BIT OS VMs where GPU capacity and quota are available. A gated Cloud Run provisioner and Beyond ID browser gateway are included; cloud session start remains disabled until the operator configures and deploys them.

## Machine profiles and browser access

`machine-profiles.json` is the source for the profile cards and accepted plan IDs. The web app reads it when rendering the page and the request API validates against the same catalog.

| Profile | vCPU | RAM | Capped SSD | GPU |
| --- | ---: | ---: | ---: | --- |
| Launch | 4 | 16 GB | 256 GB | None |
| Build | 8 | 32 GB | 512 GB | Optional, subject to capacity |
| Power | 16 | 64 GB | 1 TB | Required, subject to quota and region |

These are initial product targets. Select compatible Compute Engine machine types only after checking regional capacity, GPU quota, disk type, and current price. Do not silently substitute a different profile. Keep each VM disk at or below the selected cap.

The browser desktop uses noVNC over WebSocket. VNC binds to loopback on the session VM. websockify binds to the VM's private VPC address, with a VPC firewall allowing port 6080 only from the gateway service subnet. The gateway exchanges a 45-second Beyond ID signed ticket, validates the owner and active seat through `api/gateway-lookup.php`, and sets a host-only, HTTP-only session cookie. It does not return the VM address or a reusable noVNC URL to the browser.

The Cloud Run source, VM provisioner, browser gateway, and required VPC firewall rules are in `cloud/`. The provisioner boots a configured BIT OS Compute Engine image with no external IP, tags the VM, applies the SSD cap and a four-hour maximum runtime, and waits for private noVNC readiness. The account flow can start, stop, and resume that assigned VM. The existing `beyond-os-desktop/cloud/` QEMU harness remains a local/test adapter.

Cloud session start is disabled by default. The release catalog currently offers Home 0.2, Core 0.2, Creator 0.1, Academy 0.1, and Cyber 0.1. To enable it, deploy separate Cloud Run services with `SERVICE_MODE=provisioner` and `SERVICE_MODE=gateway`, configure a verified private Compute Engine image for every offered edition through `GCP_FLAVOUR_IMAGES_JSON`, configure machine types for every offered profile, apply the VPC firewall rules, install the environment variables in this README, and map the gateway to its HTTPS subdomain. Set `BEYOND_WEBS_START_ENABLED=true` and `BEYOND_WEBS_BILLING_ENABLED=true` only after the customer billing/settlement path is ready. The app stores hourly rate snapshots and usage seconds and shows an estimate; this repository does not yet collect payment or settle those metered amounts.

The noVNC VM image must serve `/vnc.html` and `/websockify` on the VM's private address at port 6080, while its VNC socket remains on `127.0.0.1:5900`. The provisioner fails and attempts to remove a new VM if the private noVNC viewer never becomes ready. Compute Engine stops each seat after its configured maximum run duration; the persistent boot disk is retained and can continue to accrue storage charges until the instance is deleted.

See [`cloud/README.md`](../beyond-os-desktop/cloud/README.md) for deployment and networking steps. Fill out `cloud/.env.example` with project-specific values outside source control.

## Host configuration

1. Create an HTTPS subdomain for `hosting.beyondimagination.co.technology` in the hosting control panel.
2. Map its document root to the deployed `beyond-webs/` folder.
3. The production Beyond host shares sessions automatically. Set this optional PHP environment variable on the primary Beyond ID host when an explicit deployment value is preferred:

   ```text
   BEYOND_WEBS_ORIGIN=https://hosting.beyondimagination.co.technology
   ```

4. Confirm that `https://hosting.beyondimagination.co.technology/` loads and that **Continue with Beyond ID** returns to the same subdomain after sign-in.

Beyond ID defaults to this exact HTTPS host and also restricts an explicit `BEYOND_WEBS_ORIGIN` to it. This prevents sign-in returns from going to another external site.
