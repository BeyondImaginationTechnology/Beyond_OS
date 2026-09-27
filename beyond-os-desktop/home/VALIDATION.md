# Home v0.2 validation — 2026-09-26

Status: **Home 0.2 development candidate built and booted under QEMU/OVMF UEFI.**
This is not a production release. The configured Home page rendered in the
guest browser after a delay, but external HTTPS navigation remains unverified.
Installation to a target disk was not completed; media playback and hardware
acceptance remain unverified.

The latest v0.2 artifact hashes and boot results are recorded in the
“Home v0.2 release identity rebuild” section below. Earlier hash entries are
historical builds and are not the current downloads.

## Windows Wizard build

- `homeOS.exe` built on 2026-09-27 from the shared USB creator. It enables
  the Home v0.2 candidate, verifies the compressed USB image against the
  published SHA-256 manifest, expands it locally, and writes the selected USB
  disk only after explicit erase confirmation. SHA-256:
  `62b56ca22befc88a991ccecd68474b9577a400db8cfadf67356da16025ebc898`
  (25,600 bytes). It is not Authenticode-signed.

## Initial Home 0.1 full image build

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
  image. SHA-256 after the rainforest taskbar and icon refresh:
  `d69d8046c1a84077490af889d1979729680a6d988b10c91930d4a4c6a66e82f2`.
- `bit-os-home-0.1-installer.img`: 2,182,107,136 bytes; `fdisk` identified
  a GPT with a 32 MiB EFI system partition and a 2 GiB Linux root partition.
  SHA-256 after the rainforest taskbar and icon refresh:
  `f46e658fa542c53e3243df327dcd9b63336e7281b17169207079803898c3a629`.
- `rootfs.ext2` SHA-256 after the rainforest taskbar and icon refresh:
  `18579eb921e7f98161d5996350e28567043aa6cc0dd348c9a331e602b08c9d35`.
  All three current entries passed `sha256sum -c`. The images
  remain on the VM's persistent disk under
  `beyond-os-desktop/home/out/installer-output/images/`.

## UEFI boot

- Booted the final ISO with QEMU 10.0.13 and OVMF 4M firmware on the same VM,
  using TCG software emulation, standard VGA, a virtual NIC, and USB input.
  The Home graphical session reached its 1280×800 desktop. The current build
  shows the rainforest wallpaper, desktop file/folder icons, no top header,
  and the emerald glass taskbar. [Captured desktop](assets/home-qemu-uefi.png).
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
  initial page content stayed blank. The remote display path and app launch
  work; browser rendering and navigation still need diagnosis.

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

## Emerald taskbar refresh — 2026-09-26

- Rebuilt the Home package and regenerated the ISO, GPT installer image, and
  root filesystem. The Home source cross-compiled with `-Wall -Wextra -Werror`.
- Verified all three entries in the generated `SHA256SUMS` manifest. Image sizes
  are unchanged; the hashes above correspond to this refreshed build.
- The Home flavor now has a dark emerald glass taskbar with green Start and
  launcher buttons. The top branding strip, top clock, and desktop corner hint
  were removed so the rainforest wallpaper fills the desktop above the taskbar.
- Rebooted the updated ISO through the running QEMU/OVMF session. A QEMU
  monitor capture shows the refreshed 1280×800 desktop. The guest and GCP test
  VM remain running for noVNC testing.

## Rainforest taskbar and icon pass — 2026-09-26

- Rebuilt Home with `-Wall -Wextra -Werror`, regenerated the ISO and installer
  images, and verified every entry in the new `SHA256SUMS` manifest.
- The taskbar now uses a cropped strip of the Amazon rainforest wallpaper under
  a translucent emerald tint. Its quick launch icons are a folder, a globe, and
  a play symbol; Start uses a leaf glyph.
- Rebooted the ISO in QEMU/OVMF and captured the live 1280×800 desktop at
  [assets/home-qemu-uefi.png](assets/home-qemu-uefi.png). The GCP test VM stays
  running for interactive testing.

## Historical Home 0.2 UI preview before identity rebuild — 2026-09-26

- Rebuilt Beyond Home 0.2.0-dev.1 with the existing Buildroot output on
  `bit-os-core-test-a`. The Home package compiled with `-std=c11 -Wall -Wextra
  -Werror`; the full ISO and GPT installer image generation completed.
- `bitHomeos.iso`: 348,274,688 bytes; bootable ISO 9660. SHA-256:
  `fc99f523ea2a34e232a30fbc8503c54ac680d0934a4e2213164ae5fe1f1a9d03`.
- `bit-os-home-0.1-installer.img`: 2,182,107,136 bytes; GPT with a 32 MiB EFI
  System partition and 2 GiB Linux root partition. SHA-256:
  `77ddc5065c077b3af9fe5403dabd6a98e9eddad23ad6b48ccedff6d0184a9903`.
- `rootfs.ext2`: 2,147,483,648 bytes; SHA-256:
  `3d0e74ba93ee7910ca0beecf3976bcb2c3e70dee367b2b09165631db1a0bc887`.
  All three pass the generated `SHA256SUMS` manifest.
- The default 1280×800 wallpaper is a quiet, people-free movie-night room with
  a couch, blanket, popcorn, plants, and gentle TV glow. Start includes a
  wallpaper picker for this scene and the preserved Amazon rainforest image;
  the choice is stored in `~/.config/beyond-home/wallpaper`. The taskbar keeps
  its rainforest texture whichever background is selected.
- Launcher cards, wallpaper choices, taskbar buttons, and file rows now use
  rounded corners and hover highlights. Desktop folders use a layered emerald
  folder symbol. Start-menu descriptions were shortened and clipped to each
  card so text stays inside its bounds. The 1280×800 screenshot below was
  captured from the refreshed QEMU guest after booting the rebuilt ISO under
  OVMF UEFI with TCG emulation. The guest is available in noVNC on the running
  test VM.

## Remaining release checks

- The final USB installer image has not been booted or used to install onto a
  disposable disk. Test both selected-partition and confirmed whole-disk
  installation paths and then boot the installed system with UEFI.
- Browser navigation, media playback/audio output, notes persistence,
  hardware graphics and input, and real hardware boot remain unverified.
- These are unsigned development images. Do not publish them as a production
  download before the remaining checks in `RELEASE.md`.

## Home v0.2 release identity rebuild — 2026-09-26

- Updated the edition identity to `home-0.2-dev.1` across the boot splash,
  operating-system release metadata, desktop About panel, GRUB menus, installer,
  and generated GPT image name. The previous verified image files still carried
  v0.1 identity and are not the v0.2 release artifacts.
- Completed the clean identity rebuild on `bit-os-core-test-a` using
  Buildroot 2026.02.3 and `BR2_JLEVEL=2`. The build log is
  `/home/goldenghostog/home-v0.2-build.log` on the VM. The Home package
  integration check passed; the build generated the ISO, root filesystem, and
  GPT installer image.
- `bitHomeos.iso`: 354,426,880 bytes; `file` identifies a bootable ISO 9660
  image. SHA-256: `bb916505624ac46eb7ed7e6fb4c363893174e3080a244a11ec570e8154dbf1e2`.
- `bit-os-home-0.2-installer.img`: 2,182,107,136 bytes; `file` identifies an
  MBR image with a protective GPT record. SHA-256:
  `0d04be52a4ef9107ad8c036529947656b1e3a4ece872469fc7dd0132ad9ad078`.
- `rootfs.ext2`: 2,147,483,648 bytes; SHA-256:
  `dabd8b285e102fc91d70ab7f788b9d845fb106456903ccccdd7aeae37136bda0`.
  All three raw artifact entries passed `sha256sum -c`.
- Deterministic gzip downloads were generated with `gzip -1n` and verified:
  `bitHomeos.iso.gz` SHA-256
  `f965ba269bb4ae9dabb0791db1d4132993695489cf8bbb051824adda92c63dd2`,
  `bit-os-home-0.2-installer.img.gz` SHA-256
  `4c2fb33b77a62aa7d0c2d4f42fb45034d0d892f4adbbbd0e1827a127d9fce475`.
- Copied both compressed files and `SHA256SUMS` from the build VM into local
  release staging. Their SHA-256 hashes match the manifest; complete streamed
  decompression reproduced the expected raw artifact sizes and SHA-256 hashes.
- Fresh OVMF UEFI boot displayed both GRUB entries as Home v0.2. The Try entry
  reached the 1280×720 desktop with the movie-night wallpaper, desktop folders,
  and rainforest taskbar. The boot splash displayed `HOME EDITION v0.2`.
- The installer entry reached the text installer and enumerated only the ISO
  (`/dev/sr0`) and a 5 GiB disposable QEMU disk (`/dev/vda`). The test did not
  complete target selection or write an installation; no installed-disk boot
  is claimed.
- Browser launch produced a MiniBrowser window, but its page remained blank at
  the configured Home site during this identity-rebuild check. A later noVNC
  check superseded the blank-page observation: the configured Home page rendered
  after a delay. External HTTPS navigation is still unverified.
  Media playback/audio, notes persistence, graphics/input hardware, and real
  hardware boot remain unverified. The Media app's empty-library state was
  observed in an earlier v0.2 UI session; no media fixture was played.

## Browser follow-up — 2026-09-26

- In the later noVNC session, MiniBrowser eventually rendered the configured
  BIT OS Home website, correcting the earlier blank-page observation. The
  software-emulated guest was slow to paint the page.
- Entering `https://example.com` left MiniBrowser displaying “The URL can't be
  shown.” This external-navigation check did not pass; DNS, TLS, and general
  browser network access still need diagnosis. Do not treat the Browser as
  release-ready based on the configured-site render alone.

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
