#!/bin/sh
set -eu

board_dir=$(dirname "$0")
root_image="$BINARIES_DIR/rootfs.ext2"
part_uuid=$(dumpe2fs "$root_image" 2>/dev/null | sed -n 's/^Filesystem UUID: *\(.*\)/\1/p')
[ -n "$part_uuid" ]
product="BIT OS Core v0.2"
image="bit-os-core-0.2-installer.img"
iso="bitCoreos.iso"
if [ -r "$TARGET_DIR/usr/share/beyond-imagination-os/flavour" ] &&
   [ "$(cat "$TARGET_DIR/usr/share/beyond-imagination-os/flavour")" = creator ]; then
    product="BIT OS Creator v0.1"
    image="bit-os-creator-0.1-installer.img"
    iso="bitCreatoros.iso"
elif [ -r "$TARGET_DIR/usr/share/beyond-imagination-os/flavour" ] &&
     [ "$(cat "$TARGET_DIR/usr/share/beyond-imagination-os/flavour")" = academy ]; then
    product="BIT OS Academy v0.1"
    image="bit-os-academy-0.1-installer.img"
    iso="bitAcademyos.iso"
elif [ -r "$TARGET_DIR/usr/share/beyond-imagination-os/flavour" ] &&
     [ "$(cat "$TARGET_DIR/usr/share/beyond-imagination-os/flavour")" = sentinel ]; then
    product="BIT OS Sentinel v0.1"
    image="bit-os-sentinel-0.1-installer.img"
    iso="bitSentinelos.iso"
elif [ -r "$TARGET_DIR/usr/share/beyond-imagination-os/flavour" ] &&
     [ "$(cat "$TARGET_DIR/usr/share/beyond-imagination-os/flavour")" = gaming ]; then
    product="BIT OS Gaming v0.1"
    image="bit-os-gaming-0.1-installer.img"
    iso="bitGamingos.iso"
fi

install -d "$BINARIES_DIR/efi-part/EFI/BOOT"
sed -e "s/%PARTUUID%/$part_uuid/g" -e "s/%PRODUCT%/$product/g" \
    "$board_dir/grub.cfg.in" > "$BINARIES_DIR/efi-part/EFI/BOOT/grub.cfg"
sed "s/%PARTUUID%/$part_uuid/g" "$board_dir/genimage-uefi.cfg.in" > "$BINARIES_DIR/genimage-uefi.cfg"
support/scripts/genimage.sh -c "$BINARIES_DIR/genimage-uefi.cfg"
if [ "$image" != bit-os-core-0.2-installer.img ]; then
    mv "$BINARIES_DIR/bit-os-core-0.2-installer.img" "$BINARIES_DIR/$image"
fi
cp "$BINARIES_DIR/rootfs.iso9660" "$BINARIES_DIR/$iso"
sha256sum "$BINARIES_DIR/$image" "$BINARIES_DIR/$iso" "$root_image" > "$BINARIES_DIR/SHA256SUMS"
