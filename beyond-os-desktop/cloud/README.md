# Beyond Imagination OS cloud GUI

This directory documents the browser-only Google Cloud test path for Core. It
is a single-session QEMU validation harness, not the production multi-user VPS
profile or session broker.

## Beyond Webs services

`beyond-webs/cloud/` contains a Cloud Run provisioner and a noVNC WebSocket
gateway. Deploy the same container as two services, setting `SERVICE_MODE` to
`provisioner` and `gateway` respectively. Give only the provisioner service
account Compute Engine permissions. The gateway needs VPC egress to the seat
VMs and HTTPS access to the Beyond Webs lookup endpoint; it does not need
Compute Engine IAM permissions.

The provisioner accepts signed server-to-server calls from the Beyond Webs PHP
API. It creates one private-address Compute Engine VM for a request, attaches
the configured machine profile, enforces a maximum run duration (default four
hours, which stops the VM), and waits for noVNC to answer on the private VPC
address. It attaches no external IP. The boot disk persists when the VM stops
and is automatically deleted only if the VM itself is deleted.

The gateway accepts a 45-second HMAC ticket minted by the authenticated Beyond
ID PHP session. On ticket exchange it checks the user and request against
`beyond-webs/api/gateway-lookup.php`, then sets a host-only, HTTP-only cookie.
It repeats the seat lookup for viewer resources and the WebSocket handshake;
the browser never learns the VM's private IP. Run the gateway at a dedicated
HTTPS host such as `vps.beyondimagination.co.technology` and map that host to
the gateway Cloud Run service.

Deploy the gateway with Cloud Run Direct VPC egress on a dedicated subnet. The
included `networking.tf` allows TCP 6080 from that subnet to VMs tagged
`beyond-webs-session`, then denies that port from every other source. Confirm
that no higher-level firewall policy or other rule grants broader access.
VNC TCP 5900 remains loopback-only on each VM; neither VNC nor noVNC receives a
public IP or public firewall rule.

The profile-to-machine-type map is operator configuration because Compute
Engine type names, GPU availability, and quota depend on the region. Configure
all three mappings and a compatible BIT OS image before enabling provisioning.
The Power profile cannot start until a supported accelerator mapping is also
configured. All profile resources remain targets until that image boots and
passes the noVNC readiness check.

Cloud Run WebSocket connections are subject to the service request timeout.
The browser's noVNC client reconnects if that limit is reached; the VM's own
maximum run duration is the separate session cost cap. See [Cloud Run WebSocket
guidance](https://docs.cloud.google.com/run/docs/triggering/websockets) and
[Direct VPC egress setup](https://docs.cloud.google.com/run/docs/configuring/vpc-direct-vpc).
Set the provisioner Cloud Run request timeout to 300 seconds; the PHP caller
uses a 270-second timeout while the VM operation and private noVNC readiness
checks complete.

### Required PHP environment

Configure these on the Beyond Webs PHP host. Generate separate random secrets
of at least 32 characters for each secret variable; the values are shared only
between the named services and PHP endpoints.

```text
BEYOND_WEBS_PROVISIONER_URL=https://<provisioner-cloud-run-host>
BEYOND_WEBS_PROVISIONER_SECRET=<random-secret-32-or-more-characters>
BEYOND_WEBS_GATEWAY_ORIGIN=https://vps.beyondimagination.co.technology
BEYOND_WEBS_GATEWAY_TICKET_SECRET=<separate-random-secret>
BEYOND_WEBS_GATEWAY_SHARED_SECRET=<separate-random-secret>
BEYOND_WEBS_PROFILE_RATES_JSON={"Launch":{"amount":"<confirmed-rate>","currency":"CAD"},"Build":{"amount":"<confirmed-rate>","currency":"CAD"},"Power":{"amount":"<confirmed-rate>","currency":"CAD"}}
BEYOND_WEBS_START_ENABLED=false
BEYOND_WEBS_BILLING_ENABLED=false
```

The gateway service receives `SERVICE_MODE=gateway`, `BEYOND_WEBS_APP_ORIGIN`
set to the exact Beyond Webs HTTPS origin, `BEYOND_WEBS_LOOKUP_URL` set to
`https://hosting.beyondimagination.co.technology/api/gateway-lookup.php`,
`BEYOND_WEBS_GATEWAY_TICKET_SECRET`, and
`BEYOND_WEBS_GATEWAY_COOKIE_SECRET` (the same value as PHP's
`BEYOND_WEBS_GATEWAY_SHARED_SECRET`). Attach the dedicated Direct VPC egress
subnet to this service.

The provisioner receives `SERVICE_MODE=provisioner`,
`BEYOND_WEBS_PROVISIONER_SECRET`, `GCP_PROJECT_ID`, `GCP_ZONE`,
`BIT_OS_IMAGE=projects/<image-project>/global/images/<image>`,
`GCP_NETWORK`, `GCP_SUBNETWORK`, and a value for
`SESSION_MAX_RUN_SECONDS` (default `14400`, max `43200`). Set
`GCP_PROFILE_MACHINE_TYPES_JSON` to the region-verified machine type for each
profile. Set `GCP_PROFILE_ACCELERATORS_JSON` only with the verified accelerator
type/count for eligible profiles. Use the Cloud Run service identity (no
downloaded service-account key) and grant Compute permissions to the
provisioner only. Require the VM image project to grant image read access.

Build from the `beyond-webs/` directory so the Docker build can include both
`cloud/server.mjs` and `machine-profiles.json`. Deploy the provisioner and
gateway from that same image as separate Cloud Run services. Keep both
provisioner and billing flags false until the services, image, profile maps,
rates, customer billing adapter, DNS, and firewall have all been configured.
This repo meters usage and displays estimates; it does not capture or settle
customer payments yet.

## Prototype harness architecture

```text
Library browser
  -> HTTPS endpoint protected by Google/IAP
  -> noVNC web client and WebSocket proxy
  -> private VNC socket on the Core VM
  -> Core Xorg :0 and Openbox session
```

The VM must not expose VNC or noVNC directly to the public internet. The
browser endpoint should require the Google account used for the test project.
Use an incognito window on shared computers and sign out when testing ends.

For Beyond Webs production, use one isolated Compute Engine VM per active seat.
The initial product targets are Launch (4 vCPU, 16 GB RAM, 256 GB SSD), Build
(8 vCPU, 32 GB RAM, 512 GB SSD), and Power (16 vCPU, 64 GB RAM, 1 TB SSD).
These are requested targets only; verify machine availability, disk pricing,
GPU quota, and region support before provisioning. The Beyond Webs provisioner
and gateway are documented above; this section describes the local prototype
harness and its intended network boundary.

The session gateway authenticates the Beyond ID user and authorizes that user
for the specific active VM before proxying its WebSocket. Keep VNC loopback-only
and bind websockify to the private VPC interface, issue short-lived
session-bound access, and revoke it when the VM stops. Do not expose ports
5900 or 6080 publicly or save a raw noVNC URL in the request record.

## Guest requirements

The Core image already starts Xorg and Openbox locally. A cloud image still
needs a private VNC server bound to loopback and a noVNC/WebSocket adapter.
Those services belong in the cloud deployment layer, not in the base flavour's
public network policy. The first cloud milestone is a browser smoke test that
confirms the Core splash, Openbox session, mouse, keyboard, and shutdown path.

## Host adapter

The repository includes a host-side adapter for the disposable QEMU test image:

```sh
sudo apt-get install qemu-system-x86 websockify
git clone https://github.com/novnc/noVNC.git /opt/novnc
export NOVNC_DIR=/opt/novnc
bash cloud/launch-browser-gui.sh core/out/output/images
```

`launch-browser-gui.sh` starts QEMU VNC on `127.0.0.1:5900` and
`serve-novnc.sh` on `127.0.0.1:6080`. The scripts reject non-loopback bind
addresses. The remaining deployment step is to publish port 6080 through an
authenticated HTTPS/IAP reverse proxy; do not add firewall rules for 5900 or
6080.

## Security acceptance

- No public TCP 22, 5900, or 6080 listener.
- HTTPS access is authenticated by Google/IAP.
- The VNC socket binds to `127.0.0.1` only.
- Shared-library-computer testing uses a private browser window and no saved
  credentials.
