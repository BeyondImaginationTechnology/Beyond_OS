#!/usr/bin/env bash
set -euo pipefail

# Host-side browser test harness. It launches the Core image's disposable
# QEMU session with VNC and noVNC on loopback only. An authenticated HTTPS/IAP
# reverse proxy must be configured separately before remote browser access.
cloud_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
core_dir=$(cd -- "$cloud_dir/../core" && pwd)
image_dir=${1:-"${BEYOND_BUILD_DIR:-$core_dir/out}/output/images"}

[[ -f "$image_dir/bzImage" && -f "$image_dir/rootfs.ext2" ]] || {
    echo "Build Core first; expected images in $image_dir" >&2
    exit 1
}
command -v websockify >/dev/null || { echo "websockify is required." >&2; exit 1; }
[[ -n "${NOVNC_DIR:-}" && -f "$NOVNC_DIR/vnc.html" ]] || {
    echo "Set NOVNC_DIR to a noVNC checkout containing vnc.html." >&2
    exit 1
}

qemu_log=${BEYOND_QEMU_LOG:-/tmp/beyond-core-qemu.log}
novnc_log=${BEYOND_NOVNC_LOG:-/tmp/beyond-core-novnc.log}
nohup env BEYOND_QEMU_VNC_LISTEN=127.0.0.1:0 \
    "$core_dir/run-qemu.sh" "$image_dir" >"$qemu_log" 2>&1 &
echo $! > /tmp/beyond-core-qemu.pid

for _ in {1..30}; do
    if command -v ss >/dev/null && ss -ltn | grep -q ':5900 '; then break; fi
    sleep 1
done
ss -ltn | grep -q ':5900 ' || { echo "QEMU VNC did not start; see $qemu_log" >&2; exit 1; }

nohup env BEYOND_NOVNC_LISTEN=127.0.0.1:6080 BEYOND_VNC_TARGET=127.0.0.1:5900 \
    "$cloud_dir/serve-novnc.sh" >"$novnc_log" 2>&1 &
echo $! > /tmp/beyond-core-novnc.pid
echo "noVNC loopback endpoint: http://127.0.0.1:6080/vnc.html?host=127.0.0.1&port=6080"
echo "QEMU log: $qemu_log"
echo "noVNC log: $novnc_log"
