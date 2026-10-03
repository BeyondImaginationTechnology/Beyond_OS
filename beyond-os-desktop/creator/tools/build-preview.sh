#!/bin/sh
# Compile Creator Hub against an existing BIT OS Core Buildroot toolchain.
set -eu
output=${1:?usage: build-preview.sh PATH_TO_CORE_OUTPUT [OUTPUT_BINARY]}
binary=${2:-../creator-preview}
creator_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
export PKG_CONFIG_SYSROOT_DIR="$output/staging"
export PKG_CONFIG_LIBDIR="$output/staging/usr/lib/pkgconfig"
flags=$("$output/host/bin/pkg-config" --cflags --libs sdl2 SDL2_ttf)
"$output/host/bin/x86_64-buildroot-linux-musl-gcc" \
    -std=c11 -Wall -Wextra -Werror "$creator_root/src/creator.c" \
    -o "$binary" $flags -lm
