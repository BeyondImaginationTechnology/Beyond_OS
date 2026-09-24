#!/bin/sh
set -eu
target=$(realpath "$1")
[ "$target" != / ] || exit 1
[ -d "$target/etc" ] && [ -d "$target/usr" ] || exit 1
# Our session owns display :0. The upstream default would start a second server.
rm -f "$target/etc/init.d/S40xorg"
# Normalize executable modes even when the checkout originated on Windows.
chmod 0755 "$target/etc/init.d/S00beyond-live-runtime" "$target/etc/init.d/S01beyond-splash" "$target/etc/init.d/S99beyond-core"
chmod 0755 "$target/usr/bin/beyond-session" "$target/usr/bin/beyond-user-session" "$target/usr/bin/bit-install-core"
# The skeleton may use a symlink; remove it inside the target before writing.
rm -f "$target/etc/os-release"
cat > "$target/etc/os-release" <<'EOF'
NAME="Beyond Imagination OS Core Edition"
PRETTY_NAME="BIT OS Core v0.2"
ID=beyond-os
VERSION="0.2"
VERSION_ID="0.2"
BUILD_ID="core-dev.1"
HOME_URL="https://beyondimagination.co.technology/"
EOF
install -d -m 0755 "$target/usr/lib" "$target/home/home/Documents"
rm -f "$target/usr/lib/os-release"
cp "$target/etc/os-release" "$target/usr/lib/os-release"
printf '%s\n' 'Welcome to BIT OS Core v0.2.' 'This independent image is built from upstream Linux components.' > "$target/home/home/Documents/Welcome.txt"
printf '%s\n' 'BIT OS Core v0.2' > "$target/etc/issue"
printf '%s\n' 'BIT OS Core v0.2' > "$target/etc/motd"
