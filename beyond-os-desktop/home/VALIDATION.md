# Validation — 2026-09-05

Status: **development foundation; no complete Linux image has been built or booted.**

## Completed

- Verified the downloaded Buildroot 2026.02.3 archive against the SHA-256 recorded
  in `buildroot.lock`.
- Resolved the external configuration against that upstream source using
  Kconfiglib: all 43 requested settings retained; root password login and SSH
  disabled. This checks configuration dependencies, not a full GNU Make build.
- All shell scripts passed Bash syntax checks.
- Native Home source compiled with SDL2 2.32.10 and SDL2_ttf 2.24.0 using
  `-std=c11 -Wall -Wextra -Werror` for a Windows validation executable.
- The final Home source was also compiled as part of the native storage tests.
  Tests passed for note creation/replacement, save-failure retention, bounded
  paths, UTF-8 text preview, binary rejection, and empty-folder navigation.
- `splash.c` compiled to an x86-64 Linux/musl object with warnings treated as
  errors. Its framebuffer behavior has not yet run against a Linux kernel.
- Captured and visually inspected `assets/home-preview.png` from the native
  desktop renderer on Windows, and inspected `assets/boot-preview.png`.
- Python source syntax, LF source line endings, and Git whitespace checks passed.
- No inherited distribution wording remains in the desktop source tree.

## Not validated here

- The post-build integration test could not execute through Windows Git Bash:
  subprocess creation failed with `fork: Resource temporarily unavailable`
  / Windows `0xC0000142`. Run `python3 tests/post-build-test.py` on Linux.
- The complete toolchain, kernel, root filesystem, X.Org startup and non-root
  session must be built and booted on Linux/QEMU.
- VM notes persistence, display/input behavior, network connectivity, and real
  hardware are untested.
- The preview images are not VM screenshots. No installer ISO, Live USB image,
  signed release, updater or production-ready Home 1.0 is being delivered.

The next acceptance step is a clean Linux build followed by the foundation
checks in `RELEASE.md`.

## UEFI installer source status

The repository now includes an unbuilt UEFI USB installer candidate. Source
review and Bash syntax checks passed for its image-generation and installation
scripts. Its GRUB menu separates a non-installing Try Home session from an
installer session. The installer source supports a selected existing Linux
partition or an explicitly confirmed whole non-USB disk, where it creates a GPT
EFI/Home layout. It has not been run, booted, or tested on a disposable disk
yet. Treat it as unvalidated source.

The installer configuration also requests Buildroot's UEFI ISO9660 output for
the Try Home path. No ISO has been built or booted; its firmware configuration,
live desktop behavior, and checksum output remain unvalidated.

On the Windows build workstation, both `wsl --install --distribution Debian`
and the direct-download variant stopped before installing a distribution with
`WSL/CallMsi/Install/REGDB_E_CLASSNOTREG` (Class not registered). No WSL
distribution, VM, USB image, or disk partition was created by those attempts.
