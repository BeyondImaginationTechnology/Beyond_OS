# Beyond Imagination OS Core validation

Status: **the BIT OS Core v.02 installer candidates are published for testing; Core has not passed its release gates and is not a stable v.02 release.**

## Candidate evidence

The remote build produced these artifacts in the installer output directory:

- `bitCoreos.iso` — 81 MiB (filesystem listing on the test VM).
- `bit-os-core-1.0-installer.img` — 2.1 GiB (filesystem listing on the test VM).

The release-host URLs are retained as candidate endpoints, but the current VM artifacts have not yet been confirmed rehosted there:

- `https://os.beyondimagination.co.technology/releases/core/1.0/bitCoreos.iso`
- SHA-256: `63586535422c959c38f2acea8261513497649757e2997ac51263c031e5ec9ef5`
- `https://os.beyondimagination.co.technology/releases/core/1.0/bit-os-core-1.0-installer.img`
- SHA-256: `356fd963c02ccdc68ec5edc3b8c390b8ff45b382c3a5855c5fd9d8de6496356d`
- `https://os.beyondimagination.co.technology/releases/core/1.0/SHA256SUMS`

Treat the VM artifacts as test candidates only until the release-host files and every gate below are revalidated.

## Source checks completed

- Core configuration, identity, UEFI artifact names and installer entry are tracked separately from other Beyond Imagination OS editions.
- The installer is UEFI-only and requires explicit target input. Selected-partition mode preserves the existing EFI System Partition; whole-disk mode requires an exact destructive confirmation.
- Installer startup is connected to `/dev/console`. The current USB GRUB installer entry selects `console=tty1`; serial interaction must be verified separately and is not yet a passed check.
- Portable Python tooling compiles successfully. The post-build shell test requires a functioning Linux/POSIX shell and must be rerun on the build VM.

## Still required

### Google Cloud checkpoint (2026-09-22)

- The Debian test host has QEMU and OVMF installed, but `/dev/kvm` is unavailable; the smoke tests below used bounded software emulation.
- The Core checkout is present at `/home/goldenghostog/Beyond_OS/beyond-os-desktop/core` on VM `bit-os-core-test-a`. Installer output is present in `out/installer-output/images`.
- `tests/post-build-test.py` passed on the Linux host: `PASS: target identity, single display startup, and welcome document`.
- The current installer manifest reports ISO SHA-256 `63586535422c959c38f2acea8261513497649757e2997ac51263c031e5ec9ef5` and USB SHA-256 `356fd963c02ccdc68ec5edc3b8c390b8ff45b382c3a5855c5fd9d8de6496356d`.
- Revalidation of the published SHA-256 manifest and ISO currently returns HTTP 500 after retries. The links and checksums above are historical candidate evidence, not a current availability guarantee.
- The local QEMU launcher now uses Buildroot's `rootfs.ext2` output filename. Both UEFI media smoke checks are now recorded below; both disposable-disk install modes, installed-system reboot, graphical session, network, input and shutdown remain pending.

### Google Cloud UEFI checkpoint (2026-09-22)

- `bitCoreos.iso` was booted with OVMF and software emulation. Firmware loaded the Core GRUB menu and selected `Try BIT OS Core v.02`; the smoke run reached `Booting `Try BIT OS Core v.02'` before the 20-second timeout. This verifies firmware/media discovery and the ISO boot menu/kernel handoff, not the installed graphical session.
- `bit-os-core-1.0-installer.img` was booted with OVMF in QEMU snapshot mode so the source image remained unchanged. It reached the Core GRUB menu and `Booting `Try BIT OS Core v.02'` before the 20-second timeout. This verifies UEFI media discovery and kernel handoff, not the installed graphical session.
- Selected-partition installation, whole-disk installation, reboot without media, graphical session, network, input and shutdown have not passed. Do not use this candidate as the tested base for Cyber or the later editions yet.

### Google Cloud noVNC checkpoint (2026-09-22)

- The Debian test VM has `websockify` and `novnc` installed from its package repositories.
- QEMU VNC is listening on `127.0.0.1:5900` and websockify/noVNC is listening on `127.0.0.1:6080`; neither port is publicly bound.
- An authenticated Cloud Shell SSH tunnel exposed port 6080 to the Cloud Shell Web Preview. The noVNC client loaded successfully and connected to a 1280×800 canvas.
- The VM checkout predates the repository's `cloud/` harness scripts, so this smoke test used the equivalent direct QEMU/websockify commands. The guest kernel is visible through noVNC after adding the required `virtio-vga` device, but the frame then goes black; the Core splash and Openbox/SDL desktop have not passed. Mouse, keyboard, network and shutdown acceptance remain pending.

Run the following on an x86-64 Linux build host and retain the resulting logs and hashes:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
```

Then complete the UEFI and disposable-disk checks in `RELEASE.md`, including the installed-system reboot and graphical-session checks. Only after those checks and artifact checksum publication may Core be offered for download.
