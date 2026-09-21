# Beyond Imagination OS Core validation

Status: **the Core 1.0 installer candidates are published for testing; Core has not passed its release gates and is not a stable 1.0 release.**

## Candidate evidence

The remote build produced these artifacts in the installer output directory:

- `bitCoreos.iso` — 84,264,960 bytes.
- `bit-os-core-1.0-installer.img` — 2,182,107,136 bytes.

The verified test-candidate files are available from the Beyond Imagination OS release host:

- `https://os.beyondimagination.co.technology/releases/core/1.0/bitCoreos.iso`
  - SHA-256: `c4085a9d181876e262d6b8ecd729df44b73cf3ddb80dd59dea39fb245b1c255e`
- `https://os.beyondimagination.co.technology/releases/core/1.0/bit-os-core-1.0-installer.img`
  - SHA-256: `2ef9f9ab56c5427d41a77244bbdb468b8d1d53e89864a60440f8089f3fe4bae1`
- `https://os.beyondimagination.co.technology/releases/core/1.0/SHA256SUMS`

The release-host checksums matched the uploaded files. Treat these files as test candidates only until every gate below is complete.

## Source checks completed

- Core configuration, identity, UEFI artifact names and installer entry are tracked separately from other Beyond Imagination OS editions.
- The installer is UEFI-only and requires explicit target input. Selected-partition mode preserves the existing EFI System Partition; whole-disk mode requires an exact destructive confirmation.
- Installer startup is connected to `/dev/console`. The current USB GRUB installer entry selects `console=tty1`; serial interaction must be verified separately and is not yet a passed check.
- Portable Python tooling compiles successfully. The post-build shell test requires a functioning Linux/POSIX shell and must be rerun on the build VM.

## Still required

### Google Cloud checkpoint (2026-09-13)

- The Debian test host has nested virtualization enabled, with `/dev/kvm` present, QEMU and OVMF installed.
- The source build is running from commit `6284764`; the latest observed stage is the initial GCC toolchain compilation. Installer images from this build have not yet been verified.
- `tests/post-build-test.py` passed on the Linux host before this build; this does not demonstrate boot or installation success.
- Downloading the previously published ISO returned HTTP 500 after retries. The links and checksums above are historical candidate evidence, not a current availability guarantee.
- The local QEMU launcher now uses Buildroot's `rootfs.ext2` output filename. UEFI ISO and USB menu/kernel handoff smoke checks are now recorded below; both disposable-disk install modes, installed-system reboot, graphical session, network, input and shutdown remain pending.

### Google Cloud UEFI checkpoint (2026-09-14)

- `bitCoreos.iso` was booted with OVMF and KVM. Firmware loaded the Core GRUB menu and selected `Try Beyond Imagination OS Core Edition 1.0`; the smoke run was then stopped by its timeout (`124`). This verifies firmware/media discovery and the ISO boot menu, not the installed graphical session.
- `bit-os-core-1.0-installer.img` initially stopped with `error: unknown filesystem` after its GRUB menu. The GRUB configuration was hardened to load GPT/FAT modules and use an explicit `(hd0,gpt1)/bzImage` path; after rebuilding, the USB image reached `Booting `Try BIT OS Core Edition 1.0'` and remained running until the 25-second smoke timeout (`124`). This verifies UEFI media discovery and kernel handoff, not the installed graphical session.
- Selected-partition installation, whole-disk installation, reboot without media, graphical session, network, input and shutdown have not passed. Do not use this candidate as the tested base for Cyber or the later editions yet.

Run the following on an x86-64 Linux build host and retain the resulting logs and hashes:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
```

Then complete the UEFI and disposable-disk checks in `RELEASE.md`, including the installed-system reboot and graphical-session checks. Only after those checks and artifact checksum publication may Core be offered for download.
