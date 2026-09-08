# BIT OS Core validation

Status: **installer candidates were assembled in the remote Linux build environment; Core has not passed its release gates and must not be published as 1.0.**

## Candidate evidence

The remote build produced these artifacts in the installer output directory:

- `bitCoreos.iso` — 84,264,960 bytes.
- `bit-os-core-1.0-installer.img` — 2,182,107,136 bytes.

The artifacts have not yet been copied into a release store or accompanied by retained logs and checksums in this repository. Treat them as test candidates only.

## Source checks completed

- Core configuration, identity, UEFI artifact names and installer entry are tracked separately from other BIT OS editions.
- The installer is UEFI-only and requires explicit target input. Selected-partition mode preserves the existing EFI System Partition; whole-disk mode requires an exact destructive confirmation.
- The installer entry sends output to both the local console and serial console, and installer startup is connected to `/dev/console` for headless QEMU observation.
- Portable Python tooling compiles successfully. The post-build shell test requires a functioning Linux/POSIX shell and must be rerun on the build VM.

## Still required

Run the following on an x86-64 Linux build host and retain the resulting logs and hashes:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
```

Then complete the UEFI and disposable-disk checks in `RELEASE.md`, including the installed-system reboot and graphical-session checks. Only after those checks and artifact checksum publication may Core be offered for download.