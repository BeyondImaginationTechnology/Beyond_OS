# BIT OS Core v0.2

Beyond Imagination OS Core is a lean, independent Linux system for custom machines, older
hardware and virtual machines. It is assembled from upstream source with
Buildroot as build tooling; it does not ship another distribution's root
filesystem or branding.

## Product direction

Core starts with the smallest maintained BIT desktop foundation: Linux, musl,
BusyBox, X.Org, Openbox and the native SDL2 Core shell. The shell provides local
files, a Core note, a terminal and running-system details. Browser, media,
accounts, cloud services and optional application bundles belong in later
profiles, keeping the base image focused and understandable.

## Updates

Core includes the separate `bit-update` service boundary. It polls a signed
tab-separated manifest, verifies HTTPS downloads and SHA-256 digests, stages
changed component archives, and preserves the previous staged set for rollback.
The service is deliberately independent of the desktop process. Applying a
staged update and rebooting are separate release-gated operations; the preview
does not silently replace a running system. Configure the manifest URL and
public key in `/etc/bit/update.conf` before enabling a production channel.

## Release formats

A successful build produces a QEMU disk image and hashes. The `installer`
action creates these installer candidates:

- `bitCoreos.iso` — UEFI live-session and VM ISO.
- `bit-os-core-0.2-installer.img` — GPT USB image with a separate installer
  workflow (about 2.1 GiB raw; gzip-compressible for hosting).
- `SHA256SUMS` — hashes for the built artifacts.

## Candidate downloads

Core installer media is published for testing, not as a stable v0.2 release.
Use the [compressed ISO](https://os.beyondimagination.co.technology/releases/core/0.2/bitCoreos.iso.gz), [compressed USB image](https://os.beyondimagination.co.technology/releases/core/0.2/bit-os-core-0.2-installer.img.gz), [Windows wizard](https://os.beyondimagination.co.technology/releases/core/0.2/coreOS.exe), and [SHA-256 manifest](https://os.beyondimagination.co.technology/releases/core/0.2/SHA256SUMS). Extract the ISO before booting it; the wizard verifies and expands the compressed USB image before writing USB. Check `VALIDATION.md`; v0.2 remains a test candidate.
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
not yet passed them and must not be published as a stable v0.2 image.

## Local checks

Install the native-test host dependencies first (on Debian/Ubuntu this uses
`apt-get`; Fedora and Arch are supported by the helper as well):

```sh
sh tests/install-test-deps.sh
```

```sh
python3 tests/post-build-test.py
python3 tools/verify-config.py configs/beyond_core_x86_64_defconfig
# For an out-of-tree build, set BEYOND_BUILD_DIR or pass the .config path:
BEYOND_BUILD_DIR=/path/to/build-area python3 tools/verify-config.py configs/beyond_core_x86_64_defconfig
sh tests/verify-release-artifacts.sh
tests/run-installer-qemu.sh out/installer-output/images/bitCoreos.iso
cc -std=c11 -Wall -Wextra -Werror tests/storage-test.c -o /tmp/bitos-core-storage \
  $(pkg-config --cflags --libs sdl2 SDL2_image SDL2_ttf) -lm
```

See `VALIDATION.md` for the checks actually completed.
