# BIT OS Core validation

Status: **the Core 1.0 installer candidates are published for testing; Core has not passed its release gates and is not a stable 1.0 release.**

## Candidate evidence

The remote build produced these artifacts in the installer output directory:

- `bitCoreos.iso` — 84,264,960 bytes.
- `bit-os-core-1.0-installer.img` — 2,182,107,136 bytes.

The verified test-candidate files are available from the BIT OS release host:

- `https://os.beyondimagination.co.technology/releases/core/1.0/bitCoreos.iso`
  - SHA-256: `c4085a9d181876e262d6b8ecd729df44b73cf3ddb80dd59dea39fb245b1c255e`
- `https://os.beyondimagination.co.technology/releases/core/1.0/bit-os-core-1.0-installer.img`
  - SHA-256: `2ef9f9ab56c5427d41a77244bbdb468b8d1d53e89864a60440f8089f3fe4bae1`
- `https://os.beyondimagination.co.technology/releases/core/1.0/SHA256SUMS`

The release-host checksums matched the uploaded files. Treat these files as test candidates only until every gate below is complete.

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