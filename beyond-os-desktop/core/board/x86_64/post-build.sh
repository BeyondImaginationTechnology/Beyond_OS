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
# The selected profile metadata is installed before this hook runs.
flavour=core
[ ! -r "$target/usr/share/beyond-imagination-os/flavour" ] || flavour=$(cat "$target/usr/share/beyond-imagination-os/flavour")
# The skeleton may use a symlink; remove it inside the target before writing.
rm -f "$target/etc/os-release"
if [ "$flavour" = creator ]; then
cat > "$target/etc/os-release" <<'EOF'
NAME="Beyond Imagination OS Creator Edition"
PRETTY_NAME="BIT OS Creator v0.1 (Development Preview)"
ID=beyond-os
VERSION="0.1 (Development Preview)"
VERSION_ID="0.1"
BUILD_ID="creator-0.1-dev.1"
HOME_URL="https://beyondimagination.co.technology/"
EOF
elif [ "$flavour" = academy ]; then
cat > "$target/etc/os-release" <<'EOF'
NAME="Beyond Imagination OS Academy Edition"
PRETTY_NAME="BIT OS Academy v0.1 (Development Preview)"
ID=beyond-os
VERSION="0.1 (Development Preview)"
VERSION_ID="0.1"
BUILD_ID="academy-0.1-dev.1"
HOME_URL="https://beyondimagination.co.technology/"
EOF
else
cat > "$target/etc/os-release" <<'EOF'
NAME="Beyond Imagination OS Core Edition"
PRETTY_NAME="BIT OS Core v0.2"
ID=beyond-os
VERSION="0.2"
VERSION_ID="0.2"
BUILD_ID="core-dev.1"
HOME_URL="https://beyondimagination.co.technology/"
EOF
fi
install -d -m 0755 "$target/usr/lib" "$target/home/home/Documents"
if [ "$flavour" = creator ]; then
    install -d -m 0755 "$target/home/home/Projects" "$target/home/home/Assets" \
        "$target/home/home/Recordings" "$target/home/home/Templates" \
        "$target/home/home/Exports" "$target/home/home/Fonts"
    chown -R 1000:1000 "$target/home/home"
fi
if [ "$flavour" = academy ]; then
    install -d -m 0755 "$target/home/home/Courses/Offline" "$target/home/home/Assignments" \
        "$target/home/home/Notes" "$target/home/home/Reading"
    chown -R 1000:1000 "$target/home/home"
fi
rm -f "$target/usr/lib/os-release"
cp "$target/etc/os-release" "$target/usr/lib/os-release"
if [ "$flavour" = creator ]; then
    printf '%s\n' 'Welcome to BIT OS Creator v0.1.' 'Open Creator Hub to choose your apps, workspace, wallpaper and background playlist.' > "$target/home/home/Documents/Welcome.txt"
    printf '%s\n' 'BIT OS Creator v0.1 (Development Preview)' > "$target/etc/issue"
    printf '%s\n' 'BIT OS Creator v0.1 (Development Preview)' > "$target/etc/motd"
elif [ "$flavour" = academy ]; then
    printf '%s\n' 'Welcome to BIT OS Academy v0.1.' 'Open Learning Hub to find your course, assignment, reading and focus folders.' > "$target/home/home/Documents/Welcome.txt"
    printf '%s\n' 'BIT OS Academy v0.1 (Development Preview)' > "$target/etc/issue"
    printf '%s\n' 'BIT OS Academy v0.1 (Development Preview)' > "$target/etc/motd"
else
    printf '%s\n' 'Welcome to BIT OS Core v0.2.' 'This independent image is built from upstream Linux components.' > "$target/home/home/Documents/Welcome.txt"
    printf '%s\n' 'BIT OS Core v0.2' > "$target/etc/issue"
    printf '%s\n' 'BIT OS Core v0.2' > "$target/etc/motd"
fi
