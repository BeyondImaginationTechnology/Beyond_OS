#!/usr/bin/env bash
set -euo pipefail

# Serve noVNC only on loopback. Put Google/IAP or another authenticated HTTPS
# reverse proxy in front of this process; never expose 6080 directly.
novnc_dir=${NOVNC_DIR:-/opt/novnc}
listen=${BEYOND_NOVNC_LISTEN:-127.0.0.1:6080}
vnc_target=${BEYOND_VNC_TARGET:-127.0.0.1:5900}

case "$listen" in
    127.0.0.1:*|localhost:*) ;;
    *)
        listen_host=${listen%:*}
        case "$listen_host" in
            10.*|192.168.*) ;;
            172.*)
                second_octet=${listen_host#172.}
                second_octet=${second_octet%%.*}
                case "$second_octet" in 1[6-9]|2[0-9]|3[01]) ;; *) echo "BEYOND_NOVNC_LISTEN must bind to a private VPC address." >&2; exit 1 ;; esac
                ;;
            *) echo "BEYOND_NOVNC_LISTEN must bind to loopback or a private VPC address." >&2; exit 1 ;;
        esac
        command -v ip >/dev/null && ip -o -4 address show | grep -Fq "inet ${listen_host}/" || {
            echo "The private BEYOND_NOVNC_LISTEN address is not assigned to this VM." >&2
            exit 1
        }
        ;;
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
