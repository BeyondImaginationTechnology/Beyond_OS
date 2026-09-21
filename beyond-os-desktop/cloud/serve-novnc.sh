#!/usr/bin/env bash
set -euo pipefail

# Serve noVNC only on loopback. Put Google/IAP or another authenticated HTTPS
# reverse proxy in front of this process; never expose 6080 directly.
novnc_dir=${NOVNC_DIR:-/opt/novnc}
listen=${BEYOND_NOVNC_LISTEN:-127.0.0.1:6080}
vnc_target=${BEYOND_VNC_TARGET:-127.0.0.1:5900}

case "$listen" in
    127.0.0.1:*|localhost:*) ;;
    *) echo "BEYOND_NOVNC_LISTEN must bind to loopback." >&2; exit 1 ;;
esac
case "$vnc_target" in
    127.0.0.1:*|localhost:*) ;;
    *) echo "BEYOND_VNC_TARGET must target loopback." >&2; exit 1 ;;
esac

command -v websockify >/dev/null || {
    echo "websockify is required; install it on the Cloud VM host." >&2
    exit 1
}
[[ -f "$novnc_dir/vnc.html" ]] || {
    echo "NOVNC_DIR must contain vnc.html: $novnc_dir" >&2
    exit 1
}

exec websockify --web "$novnc_dir" "$listen" "$vnc_target"
