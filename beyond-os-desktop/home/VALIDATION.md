# Validation — 2026-09-05

Status: **installer candidate built; UEFI firmware boot path verified.**

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
- Built the complete UEFI installer image on a clean Linux build host. The
  generated `bitHomeos.iso` is a bootable ISO and the GPT USB installer image
  contains a protective MBR and GPT partition table.
- Verified the generated SHA-256 manifest for the ISO, USB installer image,
  and root filesystem after the final rebuild.
- Booted `bitHomeos.iso` in QEMU with OVMF UEFI firmware. The firmware loaded
  GRUB, displayed the Home Edition menu, and started the Try Home entry. A
  follow-up rebuild also verified that the ISO menu exposes both Try Home and
  Install Home entries.
- Patched the read-only live session to provide ephemeral `/var` and `/home`
  mounts before system services start. This removes the live-session write
  failures found in the first boot attempt; the patched candidate rebuilt
  successfully.

## Not validated here

- The post-build integration test could not execute through Windows Git Bash:
  subprocess creation failed with `fork: Resource temporarily unavailable`
  / Windows `0xC0000142`. Run `python3 tests/post-build-test.py` on Linux.
- The complete toolchain, kernel, and root filesystem have been built. The
  software-only emulator available on the build host is too slow to reach the
  graphical session in a practical test window; verify X.Org and the non-root
  session in a VM with hardware virtualization before release.
- The installer has not yet written to a disposable disk. Test both selected
  partition and confirmed whole-disk paths from the USB image and ISO, then
  verify the installed system boots through UEFI.
- VM notes persistence, display/input behavior, network connectivity after the
  final runtime patch, and real hardware remain to be tested.
- The ISO and USB installer image are unsigned installer candidates, not a
  public production release. Do not upload them to the download tab yet.

The next acceptance step is a clean Linux build followed by the foundation
checks in `RELEASE.md`.

## UEFI installer source status

The repository includes a built UEFI USB installer candidate. Source review
and Bash syntax checks passed for its image-generation and installation
scripts. Its GRUB menu separates a non-installing Try Home session from an
installer session. The installer source supports a selected existing Linux
partition or an explicitly confirmed whole non-USB disk, where it creates a GPT
EFI/Home layout. It has not been exercised against a disposable disk yet.

The installer configuration produces Buildroot's UEFI ISO9660 output for the
Try Home path. Its UEFI firmware and GRUB boot path and checksum output are
validated; final live-desktop behavior remains unvalidated.

On the Windows build workstation, both `wsl --install --distribution Debian`
and the direct-download variant stopped before installing a distribution with
`WSL/CallMsi/Install/REGDB_E_CLASSNOTREG` (Class not registered). No WSL
distribution, VM, USB image, or disk partition was created by those attempts.
