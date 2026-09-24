#!/usr/bin/env bash
# Boot installer media with a disposable secondary disk for manual release gates.
set -euo pipefail

media=${1:?usage: run-installer-qemu.sh INSTALLER_ISO_OR_IMG}
command -v qemu-system-x86_64 >/dev/null || { echo "Missing host tool: qemu-system-x86_64" >&2; exit 1; }
command -v qemu-img >/dev/null || { echo "Missing host tool: qemu-img" >&2; exit 1; }
[[ -s "$media" ]] || { echo "Installer media not found: $media" >&2; exit 1; }

ovmf_code=${BEYOND_OVMF_CODE:-}
for candidate in /usr/share/OVMF/OVMF_CODE.fd /usr/share/edk2/ovmf/OVMF_CODE.fd /usr/share/edk2/x64/OVMF_CODE.fd; do
    [[ -n "$ovmf_code" ]] || [[ -f "$candidate" ]] && { ovmf_code=${ovmf_code:-$candidate}; break; }
done
[[ -f "$ovmf_code" ]] || { echo "Set BEYOND_OVMF_CODE to an OVMF_CODE.fd file." >&2; exit 1; }
ovmf_vars_template=${BEYOND_OVMF_VARS:-${ovmf_code%OVMF_CODE.fd}OVMF_VARS.fd}
[[ -f "$ovmf_vars_template" ]] || { echo "Set BEYOND_OVMF_VARS to an OVMF_VARS.fd file." >&2; exit 1; }

work_dir=$(mktemp -d)
cleanup() {
    status=$?
    [[ "${BEYOND_KEEP_DISK:-0}" = 1 ]] || rm -rf "$work_dir"
    exit "$status"
}
trap cleanup EXIT HUP INT TERM
cp "$ovmf_vars_template" "$work_dir/OVMF_VARS.fd"
qemu-img create -f qcow2 "$work_dir/disposable-target.qcow2" "${BEYOND_TARGET_SIZE:-8G}"

media_args=(-cdrom "$media")
case "$media" in
    *.img) media_args=(-drive "file=$media,format=raw,if=virtio,readonly=on") ;;
esac
echo "Disposable target: $work_dir/disposable-target.qcow2"
echo "Run selected-partition and whole-disk workflows manually; the target is disposable."
qemu-system-x86_64 -machine q35 -accel "${BEYOND_QEMU_ACCEL:-tcg}" -m 2048 -smp 2 \
    -drive "if=pflash,format=raw,readonly=on,file=$ovmf_code" \
    -drive "if=pflash,format=raw,file=$work_dir/OVMF_VARS.fd" \
    "${media_args[@]}" -drive "file=$work_dir/disposable-target.qcow2,format=qcow2,if=virtio" \
    -device virtio-vga,xres=1280,yres=800 -device qemu-xhci -device usb-tablet -device usb-kbd \
    -netdev user,id=net0 -device virtio-net-pci,netdev=net0 -serial stdio -no-reboot
