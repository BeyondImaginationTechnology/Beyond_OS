# Beyond Imagination OS cloud GUI

This directory documents the browser-only Google Cloud test path for Core.

## Target architecture

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
