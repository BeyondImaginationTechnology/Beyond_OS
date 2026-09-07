# BIT OS Core Edition 1.0

BIT OS Core is a lean, independent Linux system for custom machines, older
hardware and virtual machines. It is assembled from upstream source with
Buildroot as build tooling; it does not ship another distribution's root
filesystem or branding.

## Product direction

Core starts with the smallest maintained BIT desktop foundation: Linux, musl,
BusyBox, X.Org, Openbox and the native SDL2 Core shell. The shell provides local
files, a Core note, a terminal and running-system details. Browser, media,
accounts, cloud services and optional application bundles belong in later
profiles, keeping the base image focused and understandable.

## Release formats

A successful build produces a QEMU disk image and hashes. The `installer`
action creates these installer candidates:

- `bitCoreos.iso` — UEFI live-session and VM ISO.
- `bit-os-core-1.0-installer.img` — GPT USB image with a separate installer
  workflow.
- `SHA256SUMS` — hashes for the built artifacts.

The installer offers a selected Linux partition alongside another operating
system, or an explicitly confirmed whole non-USB disk. It does not select a
disk automatically or replace another boot manager.

## Build on Linux

Use an x86-64 Linux host or VM on a case-sensitive filesystem, with about
40 GB free disk and 8 GB RAM. The build host is not part of the shipped
product.

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
bash run-qemu.sh
```

Set `BEYOND_BUILD_DIR=/path/to/build-area` to keep generated output outside the
source tree. A release requires the acceptance checks in `RELEASE.md`; Core has
not yet passed them and must not be published as a stable 1.0 image.

## Local checks

```sh
python3 tests/post-build-test.py
cc -std=c11 -Wall -Wextra -Werror tests/storage-test.c -o /tmp/bitos-core-storage \
  $(pkg-config --cflags --libs sdl2 SDL2_ttf) -lm
```

See `VALIDATION.md` for the checks actually completed.