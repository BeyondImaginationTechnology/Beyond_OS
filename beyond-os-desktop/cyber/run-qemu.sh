#!/usr/bin/env bash
set -euo pipefail
home_source=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
image_dir=${1:-"${BEYOND_BUILD_DIR:-$home_source/out}/output/images"}
command -v qemu-system-x86_64 >/dev/null || { echo "Install qemu-system-x86_64 on the host." >&2; exit 1; }
[[ -f "$image_dir/bzImage" && -f "$image_dir/rootfs.ext4" ]] || { echo "Build the kernel and root filesystem first." >&2; exit 1; }
# Snapshot mode makes every run disposable. Remove -snapshot only on a copied image
# when explicitly testing persistence. No host disk or host folder is exposed.
accelerator=tcg
[[ -r /dev/kvm && -w /dev/kvm ]] && accelerator=kvm
exec qemu-system-x86_64 -machine q35 -accel "$accelerator" -m 2048 -smp 2 \
    -kernel "$image_dir/bzImage" \
    -append "root=/dev/vda rw rootwait console=ttyS0 quiet loglevel=3" \
    -drive "file=$image_dir/rootfs.ext4,if=virtio,format=raw" -snapshot \
    -device virtio-vga,xres=1280,yres=800 \
    -device qemu-xhci -device usb-tablet -device usb-kbd \
    -netdev user,id=net0 -device virtio-net-pci,netdev=net0 \
    -serial stdio -monitor none -no-reboot
