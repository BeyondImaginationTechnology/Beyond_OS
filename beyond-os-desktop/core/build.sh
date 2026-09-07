#!/usr/bin/env bash
set -euo pipefail
core_source=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=buildroot.lock
source "$core_source/buildroot.lock"
action=${1:-build}
case "$action" in configure|build|installer|legal-info) ;; *) echo "Usage: bash build.sh [configure|build|installer|legal-info]" >&2; exit 2 ;; esac
[[ $(uname -s) == Linux ]] || { echo "Build on a Linux host or Linux VM (not a Windows filesystem)." >&2; exit 1; }
[[ $EUID -ne 0 ]] || { echo "Run Buildroot as a normal user." >&2; exit 1; }
for command in make gcc g++ curl tar sha256sum python3 rsync cpio unzip patch; do
    command -v "$command" >/dev/null || { echo "Missing host tool: $command" >&2; exit 1; }
done
build_area=${BEYOND_BUILD_DIR:-"$core_source/out"}
mkdir -p "$build_area"
build_area=$(cd "$build_area" && pwd)
case "$core_source:$build_area" in *" "*) echo "Buildroot paths must not contain spaces." >&2; exit 1 ;; esac
archive="$build_area/buildroot-$BUILDROOT_VERSION.tar.xz"
if [[ ! -f "$archive" ]]; then
    curl --fail --location --retry 3 "https://buildroot.org/downloads/buildroot-$BUILDROOT_VERSION.tar.xz" -o "$archive.part"
    mv "$archive.part" "$archive"
fi
printf '%s  %s\n' "$BUILDROOT_SHA256" "$archive" | sha256sum --check --status
source_dir="$build_area/buildroot-$BUILDROOT_VERSION"
[[ -d "$source_dir" ]] || tar -xJf "$archive" -C "$build_area"
if [[ "$action" == installer ]]; then
    output="$build_area/installer-output"
    defconfig=beyond_core_uefi_installer_x86_64_defconfig
else
    output="$build_area/output"
    defconfig=beyond_core_x86_64_defconfig
fi
chmod +x "$core_source/board/x86_64/post-build.sh"
chmod +x "$core_source/board/x86_64/post-image-uefi.sh"
make -C "$source_dir" O="$output" BR2_EXTERNAL="$core_source" "$defconfig"
python3 "$core_source/tools/verify-config.py" "$core_source/configs/$defconfig" "$output/.config"
if [[ "$action" == configure ]]; then
    echo "Configured BIT OS. No kernel or image has been built."
    exit 0
fi
if [[ "$action" == installer ]]; then
    target=all
else
    target="${action/build/all}"
fi
make -C "$source_dir" O="$output" BR2_EXTERNAL="$core_source" "$target"
if [[ "$action" == build ]]; then
    (
        cd "$output/images"
        sha256sum bzImage rootfs.ext2 > SHA256SUMS
    )
    cp "$output/.config" "$output/images/beyond-core.config"
    printf 'BIT OS Core 1.0 images: %s/images\n' "$output"
elif [[ "$action" == installer ]]; then
    test -s "$output/images/bit-os-core-1.0-installer.img"
    test -s "$output/images/bitCoreos.iso"
    test -s "$output/images/SHA256SUMS"
    cp "$output/.config" "$output/images/beyond-core-installer.config"
    printf 'BIT OS Core 1.0 UEFI installer candidate: %s/images\n' "$output"
fi
