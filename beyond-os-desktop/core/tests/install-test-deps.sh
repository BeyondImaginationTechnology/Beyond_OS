#!/bin/sh
# Install host dependencies required by the native storage test.
set -eu

if command -v apt-get >/dev/null 2>&1; then
    sudo apt-get update
    sudo apt-get install -y build-essential pkg-config libsdl2-dev libsdl2-ttf-dev
elif command -v dnf >/dev/null 2>&1; then
    sudo dnf install -y gcc make pkgconf-pkg-config SDL2-devel SDL2_ttf-devel
elif command -v pacman >/dev/null 2>&1; then
    sudo pacman -Sy --needed --noconfirm base-devel pkgconf sdl2 sdl2_ttf
else
    printf '%s\n' "Unsupported package manager; install a C compiler, pkg-config, SDL2, and SDL2_ttf manually." >&2
    exit 1
fi

