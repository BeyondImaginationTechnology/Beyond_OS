# Home v0.1 validation — 2026-09-26

Status: **full installer candidate built; the ISO booted to the Home desktop under QEMU/OVMF UEFI.**
This is a VM acceptance result, not a release sign-off.

## Full image build

- Built Buildroot 2026.02.3 in the isolated checkout on GCP VM
  `bit-os-core-test-a`, using `BR2_JLEVEL=2` and the Home UEFI installer
  defconfig. The VM has 4 vCPUs, 16 GiB RAM, and a 200 GB persistent disk.
- The first WebKitGTK compile exhausted VM memory at higher parallelism.
  The resumed build completed with two compile jobs. A subsequent UEFI boot
  exposed a `libgpg-error` lock-size mismatch: Buildroot selected a GNU
  system configuration for the musl target. The Home build script now applies
  the Buildroot configuration correction before resolving the defconfig.
  Rebuilding `libgpg-error` and its reverse dependencies produced a 40-byte
  lock object in the target headers; the final installer build completed.
- `bitHomeos.iso`: 348,274,688 bytes; `file` identified a bootable ISO 9660
  image. SHA-256 after the Home desktop refresh:
  `91898d0d23b988d4baab6dd1070d12559e66fe494f751e6110770d8bdcc56722`.
- `bit-os-home-0.1-installer.img`: 2,182,107,136 bytes; `fdisk` identified
  a GPT with a 32 MiB EFI system partition and a 2 GiB Linux root partition.
  SHA-256 after the Home desktop refresh:
  `44db5c53bb13238a5623996d3ff4afb878d1efe2abda0719750453c303c1fa9f`.
- `rootfs.ext2` SHA-256 after the Home desktop refresh:
  `41756f6f61ecd3ef51884733912dfbaa5f11debb89c9a4b6b1957f5fe09ca60c`.
  All three current entries passed `sha256sum -c`. The images
  remain on the VM's persistent disk under
  `beyond-os-desktop/home/out/installer-output/images/`.

## UEFI boot

- Booted the final ISO with QEMU 10.0.13 and OVMF 4M firmware on the same VM,
  using TCG software emulation, standard VGA, a virtual NIC, and USB input.
  The Home graphical session reached its 1280×800 desktop. The current build
  shows the rainforest wallpaper, desktop file/folder icons, and the Start
  taskbar button. [Captured desktop](assets/home-qemu-uefi.png).
- The read-only ISO live session copies the initial `/var` and `/home` content
  into temporary writable mounts before services and the user session start.
  The final boot passed the DBus and X.Org startup failures observed in earlier
  test images.

## noVNC check — 2026-09-26

- Connected noVNC to the running QEMU session through a local SSH tunnel.
  QEMU VNC and WebSocket proxy listeners bind only to the VM loopback address;
  no public VNC port was opened. The browser displayed the live Home desktop.
  Clicking the Browser tile and sending keyboard input produced the guest's
  “Browser opened” status, confirming mouse and keyboard control.
- WebKitGTK MiniBrowser opened and showed the configured site title, but its
  page content stayed blank. The remote display path and app launch work;
  browser rendering and navigation still need diagnosis.

## Desktop interaction refresh — 2026-09-26

- Rebuilt the Beyond Home package and regenerated the installer images using
  the existing Buildroot output; the Home package cross-compiled with
  `-Wall -Wextra -Werror`. The current ISO booted under the existing QEMU/OVMF
  session and displayed the rainforest wallpaper, desktop file/folder icons,
  and Start taskbar button.
- Home now places visible files and folders on the wallpaper, saves moved icon
  positions, opens desktop folders/files on double-click, and includes a Files
  drop shelf for pinning an item to the desktop. In noVNC, `Documents` opened
  from the desktop, and dragging `Welcome.txt` from Files onto the shelf added
  its icon to the desktop. Desktop icon repositioning and double-click still
  need hands-on confirmation.
- Browser launch now reports `exec` failures and sets
  `WEBKIT_DISABLE_COMPOSITING_MODE=1` for MiniBrowser, a compatibility workaround
  for blank rendering on software display paths. The refreshed browser content
  has not yet been confirmed in the VM; verify its page and navigation in noVNC.
- The updated noVNC session is available at the existing local forwarded URL.
  The GCP VM remains running for interactive testing.

## Remaining release checks

- The final USB installer image has not been booted or used to install onto a
  disposable disk. Test both selected-partition and confirmed whole-disk
  installation paths and then boot the installed system with UEFI.
- Browser navigation, media playback/audio output, notes persistence,
  hardware graphics and input, and real hardware boot remain unverified.
- These are unsigned development images. Do not publish them as a production
  download before the remaining checks in `RELEASE.md`.

## Earlier v0.1 source checks — 2026-09-25

Before the full build, the native desktop compiled with GCC and
`-Wall -Wextra -Werror`, both Home defconfigs resolved, and the direct
configuration retained all 60 requested settings while keeping root login and
SSH disabled. The post-build integration and native storage tests passed. The
desktop renderer produced a 1280×800 BMP under SDL's dummy video driver.
At that point the VM had only 28 GiB free; its disk was expanded for this build.

## Historical prototype record — 2026-09-05

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

At that time the next acceptance step was a clean Linux build; the Home v0.1
result above supersedes that pending step.

## UEFI installer source status

The repository includes a built UEFI USB installer candidate. Source review
and Bash syntax checks passed for its image-generation and installation
scripts. Its GRUB menu separates a non-installing Try Home session from an
installer session. The installer source supports a selected existing Linux
partition or an explicitly confirmed whole non-USB disk, where it creates a GPT
EFI/Home layout. It has not been exercised against a disposable disk yet.

The installer configuration produces Buildroot's UEFI ISO9660 output for the
Try Home path. Its UEFI firmware and GRUB boot path and checksum output are
validated; final live-desktop behavior was not yet validated at that time.

On the Windows build workstation, both `wsl --install --distribution Debian`
and the direct-download variant stopped before installing a distribution with
`WSL/CallMsi/Install/REGDB_E_CLASSNOTREG` (Class not registered). No WSL
distribution, VM, USB image, or disk partition was created by those attempts.
