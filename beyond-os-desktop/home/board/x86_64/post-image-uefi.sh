#!/bin/sh
set -eu

board_dir=$(dirname "$0")
root_image="$BINARIES_DIR/rootfs.ext2"
part_uuid=$(dumpe2fs "$root_image" 2>/dev/null | sed -n 's/^Filesystem UUID: *\(.*\)/\1/p')
[ -n "$part_uuid" ]

install -d "$BINARIES_DIR/efi-part/EFI/BOOT"
sed "s/%PARTUUID%/$part_uuid/g" "$board_dir/grub.cfg.in" > "$BINARIES_DIR/efi-part/EFI/BOOT/grub.cfg"
sed "s/%PARTUUID%/$part_uuid/g" "$board_dir/genimage-uefi.cfg.in" > "$BINARIES_DIR/genimage-uefi.cfg"
support/scripts/genimage.sh -c "$BINARIES_DIR/genimage-uefi.cfg"
cp "$BINARIES_DIR/rootfs.iso9660" "$BINARIES_DIR/bitHomeos.iso"
sha256sum "$BINARIES_DIR/bit-os-home-1.0-installer.img" "$BINARIES_DIR/bitHomeos.iso" "$root_image" > "$BINARIES_DIR/SHA256SUMS"
