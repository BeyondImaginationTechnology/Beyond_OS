#!/usr/bin/env bash
set -euo pipefail
home_source=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=buildroot.lock
source "$home_source/buildroot.lock"
action=${1:-build}
case "$action" in configure|build|legal-info) ;; *) echo "Usage: bash build.sh [configure|build|legal-info]" >&2; exit 2 ;; esac
[[ $(uname -s) == Linux ]] || { echo "Build on a Linux host or Linux VM (not a Windows filesystem)." >&2; exit 1; }
[[ $EUID -ne 0 ]] || { echo "Run Buildroot as a normal user." >&2; exit 1; }
for command in make gcc g++ curl tar sha256sum python3 rsync cpio unzip patch; do
    command -v "$command" >/dev/null || { echo "Missing host tool: $command" >&2; exit 1; }
done
build_area=${BEYOND_BUILD_DIR:-"$home_source/out"}
mkdir -p "$build_area"
build_area=$(cd "$build_area" && pwd)
case "$home_source:$build_area" in *" "*) echo "Buildroot paths must not contain spaces." >&2; exit 1 ;; esac
archive="$build_area/buildroot-$BUILDROOT_VERSION.tar.xz"
if [[ ! -f "$archive" ]]; then
    curl --fail --location --retry 3 "https://buildroot.org/downloads/buildroot-$BUILDROOT_VERSION.tar.xz" -o "$archive.part"
    mv "$archive.part" "$archive"
fi
printf '%s  %s\n' "$BUILDROOT_SHA256" "$archive" | sha256sum --check --status
source_dir="$build_area/buildroot-$BUILDROOT_VERSION"
[[ -d "$source_dir" ]] || tar -xJf "$archive" -C "$build_area"
output="$build_area/output"
chmod +x "$home_source/board/x86_64/post-build.sh"
make -C "$source_dir" O="$output" BR2_EXTERNAL="$home_source" beyond_home_x86_64_defconfig
python3 "$home_source/tools/verify-config.py" "$home_source/configs/beyond_home_x86_64_defconfig" "$output/.config"
if [[ "$action" == configure ]]; then
    echo "Configured Beyond OS. No kernel or image has been built."
    exit 0
fi
make -C "$source_dir" O="$output" BR2_EXTERNAL="$home_source" "${action/build/all}"
if [[ "$action" == build ]]; then
    (
        cd "$output/images"
        sha256sum bzImage rootfs.ext4 > SHA256SUMS
    )
    cp "$output/.config" "$output/images/beyond-home.config"
    printf 'Beyond OS Home 1.0 development images: %s/images\n' "$output"
fi
