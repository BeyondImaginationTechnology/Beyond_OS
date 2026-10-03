#!/bin/sh
set -eu

board_dir=$(dirname "$0")
root_image="$BINARIES_DIR/rootfs.ext2"
part_uuid=$(dumpe2fs "$root_image" 2>/dev/null | sed -n 's/^Filesystem UUID: *\(.*\)/\1/p')
[ -n "$part_uuid" ]
product="BIT OS Core v0.2"
if [ -r "$TARGET_DIR/usr/share/beyond-imagination-os/flavour" ] &&
   [ "$(cat "$TARGET_DIR/usr/share/beyond-imagination-os/flavour")" = creator ]; then
    product="BIT OS Creator v0.1"
fi

install -d "$BINARIES_DIR/efi-part/EFI/BOOT"
sed -e "s/%PARTUUID%/$part_uuid/g" -e "s/%PRODUCT%/$product/g" \
    "$board_dir/grub.cfg.in" > "$BINARIES_DIR/efi-part/EFI/BOOT/grub.cfg"
sed "s/%PARTUUID%/$part_uuid/g" "$board_dir/genimage-uefi.cfg.in" > "$BINARIES_DIR/genimage-uefi.cfg"
support/scripts/genimage.sh -c "$BINARIES_DIR/genimage-uefi.cfg"
cp "$BINARIES_DIR/rootfs.iso9660" "$BINARIES_DIR/bitCoreos.iso"
sha256sum "$BINARIES_DIR/bit-os-core-0.2-installer.img" "$BINARIES_DIR/bitCoreos.iso" "$root_image" > "$BINARIES_DIR/SHA256SUMS"
