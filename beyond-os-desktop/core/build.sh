#!/usr/bin/env bash
set -euo pipefail
core_source=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
# shellcheck source=buildroot.lock
source "$core_source/buildroot.lock"
action=${1:-build}
case "$action" in configure|build|installer|legal-info) ;; *) echo "Usage: bash build.sh [configure|build|installer|legal-info]" >&2; exit 2 ;; esac
profile=${BEYOND_FLAVOUR:-core}
case "$profile" in core|creator|academy|sentinel|gaming) ;; *) echo "This Core builder supports core, creator, academy, sentinel, and gaming; use the Home or Cyber builder for those editions." >&2; exit 2 ;; esac
profile_version=1.0
case "$profile" in core) profile_version=0.2 ;; creator|academy|sentinel) profile_version=0.1 ;; esac
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
make -C "$source_dir" O="$output" BR2_EXTERNAL="$core_source" "$defconfig" BR2_BEYOND_PROFILE_ID="$profile"
if [[ "$profile" == creator || "$profile" == academy || "$profile" == sentinel ]]; then
    package=BEYOND_CREATOR
    [[ "$profile" == academy ]] && package=BEYOND_ACADEMY
    [[ "$profile" == sentinel ]] && package=BEYOND_SENTINEL
    sed -i "s/^BR2_BEYOND_PROFILE_ID=.*/BR2_BEYOND_PROFILE_ID=\"$profile\"/" "$output/.config"
    sed -i "s/^BR2_TARGET_GENERIC_HOSTNAME=.*/BR2_TARGET_GENERIC_HOSTNAME=\"beyond-$profile\"/" "$output/.config"
    sed -i "s/^BR2_TARGET_ROOTFS_EXT2_LABEL=.*/BR2_TARGET_ROOTFS_EXT2_LABEL=\"BEYOND_${profile^^}\"/" "$output/.config"
    if grep -q "^# BR2_PACKAGE_$package is not set$" "$output/.config"; then
        sed -i "s/^# BR2_PACKAGE_$package is not set$/BR2_PACKAGE_$package=y/" "$output/.config"
    elif ! grep -q "^BR2_PACKAGE_$package=y$" "$output/.config"; then
        printf '%s\n' "BR2_PACKAGE_$package=y" >> "$output/.config"
    fi
    make -C "$source_dir" O="$output" BR2_EXTERNAL="$core_source" olddefconfig
    profile_requested="$output/$profile-requested.config"
    cp "$core_source/configs/$defconfig" "$profile_requested"
    sed -i "s/^BR2_BEYOND_PROFILE_ID=.*/BR2_BEYOND_PROFILE_ID=\"$profile\"/" "$profile_requested"
    sed -i "s/^BR2_TARGET_GENERIC_HOSTNAME=.*/BR2_TARGET_GENERIC_HOSTNAME=\"beyond-$profile\"/" "$profile_requested"
    sed -i "s/^BR2_TARGET_ROOTFS_EXT2_LABEL=.*/BR2_TARGET_ROOTFS_EXT2_LABEL=\"BEYOND_${profile^^}\"/" "$profile_requested"
    printf '%s\n' "BR2_PACKAGE_$package=y" >> "$profile_requested"
    python3 "$core_source/tools/verify-config.py" "$profile_requested" "$output/.config"
else
    python3 "$core_source/tools/verify-config.py" "$core_source/configs/$defconfig" "$output/.config"
fi
if [[ "$action" == configure ]]; then
    echo "Configured Beyond Imagination OS. No kernel or image has been built."
    exit 0
fi
if [[ "$action" == installer ]]; then
    target=all
else
    target="${action/build/all}"
fi
make -C "$source_dir" O="$output" BR2_EXTERNAL="$core_source" BR2_BEYOND_PROFILE_ID="$profile" "$target"
if [[ "$action" == build ]]; then
    (
        cd "$output/images"
        sha256sum bzImage rootfs.ext2 > SHA256SUMS
    )
    cp "$output/.config" "$output/images/beyond-core.config"
    printf 'BIT OS %s v%s images: %s/images\n' "$profile" "$profile_version" "$output"
elif [[ "$action" == installer ]]; then
    image_name=bit-os-core-0.2-installer.img
    iso_name=bitCoreos.iso
    if [[ "$profile" == creator ]]; then image_name=bit-os-creator-0.1-installer.img; iso_name=bitCreatoros.iso; fi
    if [[ "$profile" == academy ]]; then image_name=bit-os-academy-0.1-installer.img; iso_name=bitAcademyos.iso; fi
    if [[ "$profile" == sentinel ]]; then image_name=bit-os-sentinel-0.1-installer.img; iso_name=bitSentinelos.iso; fi
    test -s "$output/images/$image_name"
    test -s "$output/images/$iso_name"
    test -s "$output/images/SHA256SUMS"
    cp "$output/.config" "$output/images/beyond-core-installer.config"
    printf 'BIT OS %s v%s UEFI installer candidate: %s/images\n' "$profile" "$profile_version" "$output"
fi
