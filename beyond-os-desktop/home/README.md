# Build Beyond OS Home

This is Beyond OS's own Linux build, with no parent distribution root filesystem.
Buildroot compiles the toolchain, Linux kernel and selected userspace software
from upstream sources. The repository supplies the Home product layer.

## What exists now

- Pinned Buildroot 2026.02.3 archive and Linux 6.18.7 QEMU configuration.
- Beyond OS identity, native framebuffer startup artwork, and an SDL2 desktop.
- Files: navigate directories and preview small UTF-8 text files.
- Notes: one local note in `~/Documents/Home Note.txt`; Ctrl+S saves it.
- Terminal: launches xterm as the unprivileged Home user.
- About: product information and the actual running kernel.
- QEMU launch script, configuration checks, and release acceptance checklist.

This is development source, not a completed 1.0 release. The first target is a
QEMU VM. The Files view shows up to 512 non-hidden entries and previews up to
8 KB of text. Notes supports append/backspace editing of up to 8 KB; it is not
yet a full text editor. Browser and media cards are deliberately absent until
working integrations exist.

## Build host

Use an x86-64 Linux host or VM with a case-sensitive Linux filesystem. Keep the
checkout and build directory on that filesystem, not a Windows/OneDrive mount.
Run as a normal user, with approximately 40 GB free disk and 8 GB RAM available
as a starting allowance. The complete build has not yet been measured.

Install the prerequisites documented by the
[Buildroot manual](https://buildroot.org/downloads/manual/manual.html#requirement):
C/C++ compiler, GNU Make, Bash, binutils, patch, diffutils, findutils, sed, awk,
tar, gzip, bzip2, xz, unzip, cpio, rsync, Perl, Python 3, curl, file, and bc.
The host's Linux distribution is only a build environment; it is not shipped.

From this directory:

```sh
bash build.sh configure  # Downloads and verifies the pinned source, resolves config
bash build.sh build      # Compiles the kernel, toolchain, desktop and root filesystem
bash run-qemu.sh         # Boots the resulting VM in disposable snapshot mode
```

Set `BEYOND_BUILD_DIR=/path/to/linux/build-area` to move generated files outside
the repository. Output is `out/output/images/` by default:

- `bzImage`: the Linux kernel.
- `rootfs.ext4`: the complete root filesystem.
- `SHA256SUMS`: hashes produced after a successful build.
- `beyond-home.config`: the resolved build configuration.

These are QEMU direct-kernel-boot artifacts, **not an installer ISO or a
USB-bootable disk**. GRUB/UEFI image assembly is a subsequent milestone.
The VM has no host disk access, shared folders, or forwarded network ports.
User-mode NAT permits outbound networking. Root password login and SSH are
disabled. The development desktop opens automatically as `home`; this is not
a production account/login design.

Notes persist on a writable root filesystem, but the default QEMU `-snapshot`
option discards all session changes on exit. For a persistence test, copy the
image and remove `-snapshot` from a copy of the launcher.

## Startup path

Linux initializes the virtual GPU and mounts the root filesystem. BusyBox init
runs `S01beyond-splash`, which centers the Beyond artwork on the framebuffer.
When services are ready, `S99beyond-home` starts X.Org and launches Openbox and
the Home shell under the non-root user. X.Org runs with a private authentication
cookie and no TCP listener. The generic X.Org startup script is removed to
prevent a competing display server.

This boot screen is a static early-userspace splash, not an encrypted-disk
password prompt. Diagnostic kernel output goes to the VM's serial console.
A framebuffer failure leaves the console available. Firmware and bootloader
branding, graphical disk unlock, multi-monitor behavior and real GPU support
still need implementation and VM/hardware testing.

## Native development

On a Linux development machine with SDL2 and SDL2_ttf development libraries:

```sh
cc -std=c11 -Wall -Wextra -Werror src/home.c -o /tmp/beyond-home \
  $(pkg-config --cflags --libs sdl2 SDL2_ttf) -lm
BEYOND_FONT=/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf /tmp/beyond-home
```

The app opens a desktop-sized window. Use Alt+F4 to close the native development
window. Tab/arrows select Home tiles, Enter opens them, Escape returns Home,
and Alt+Tab switches to other applications. The Files view uses mouse clicks
and wheel scrolling. A notes save failure keeps the note open.

## Maintenance and licensing

Buildroot creates complete filesystem images; there is no apt/dpkg package
manager in the target. Source pins are reviewable, but do not imply indefinite
security support. Review kernel and package fixes before every candidate.
Choose and implement a signed whole-image update/rollback mechanism before
shipping Home 1.0.

Run `bash build.sh legal-info` to collect Buildroot's dependency notices and
source material. Review the result and all missing-source warnings before
distribution. Original Beyond code follows the repository LICENSE. Branding
and artwork follow CONTENT_RIGHTS.md. Do not remove upstream notices.

## Checks

On Linux, run the native storage tests in a disposable folder:

```sh
cc -std=c11 -Wall -Wextra -Werror tests/storage-test.c -o /tmp/beyond-storage-test \
  $(pkg-config --cflags --libs sdl2 SDL2_ttf) -lm
fixture=$(mktemp -d)
mkdir "$fixture/empty-folder"
/tmp/beyond-storage-test "$fixture"
python3 tests/post-build-test.py
```

The native test covers note replacement, failed-save retention, path bounds,
UTF-8 previews, binary-file rejection, and empty directories. The hook test
uses a temporary target tree and checks the system identity and display-server
startup override. See `VALIDATION.md` for the checks actually run so far.