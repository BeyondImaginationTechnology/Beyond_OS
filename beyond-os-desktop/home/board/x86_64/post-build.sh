#!/bin/sh
set -eu
target=$(realpath "$1")
[ "$target" != / ] || exit 1
[ -d "$target/etc" ] && [ -d "$target/usr" ] || exit 1
# Our session owns display :0. The upstream default would start a second server.
rm -f "$target/etc/init.d/S40xorg"
# Normalize executable modes even when the checkout originated on Windows.
chmod 0755 "$target/etc/init.d/S00beyond-live-runtime" "$target/etc/init.d/S01beyond-splash" "$target/etc/init.d/S99beyond-home"
chmod 0755 "$target/usr/bin/beyond-session" "$target/usr/bin/beyond-user-session" "$target/usr/bin/bit-install-home"
# The skeleton may use a symlink; remove it inside the target before writing.
rm -f "$target/etc/os-release"
cat > "$target/etc/os-release" <<'EOF'
NAME="Beyond OS Home Edition"
PRETTY_NAME="BIT OS Home v0.1 (Development Candidate)"
ID=beyond-os
VERSION="0.1 (Development Candidate)"
VERSION_ID="0.1"
BUILD_ID="home-0.1-dev.1"
HOME_URL="https://beyondimagination.co.technology/"
EOF
install -d -m 0755 "$target/usr/lib" "$target/home/home/Documents" "$target/home/home/Media"
chown 1000:1000 "$target/home/home/Documents" "$target/home/home/Media"
rm -f "$target/usr/lib/os-release"
cp "$target/etc/os-release" "$target/usr/lib/os-release"
printf '%s\n' 'Welcome to BIT OS Home v0.1.' 'This VM development image is built from upstream Linux components.' > "$target/home/home/Documents/Welcome.txt"
printf '%s\n' 'BIT OS Home v0.1 (Development Candidate)' > "$target/etc/issue"
printf '%s\n' 'BIT OS Home v0.1 (Development Candidate)' > "$target/etc/motd"
