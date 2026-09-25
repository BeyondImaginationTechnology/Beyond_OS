# Beyond Imagination OS Core validation

Status (2026-09-25 UTC): **all four UEFI ISO/USB installation combinations completed on disposable QEMU disks, and each installed-disk boot reached the Core dashboard without installer media.** The v0.2 build is a test candidate with limited hardware coverage; see the latest checkpoint below. Older pending statements in the historical checkpoints are superseded by that record.

## Latest UEFI installation and release checkpoint — 2026-09-25 UTC

- Host: `bit-os-core-test-a`, project `project-79ff0164-14f2-4be2-ba5`, zone `northamerica-northeast2-a`; QEMU TCG with OVMF, 2 GiB RAM, and disposable test disks. The VM has no `/dev/kvm` acceleration.
- Final ISO SHA-256: `4a6d183bc58ed6a2ed5c7153fc10a2d9a5da76c25122ee2e245a5b535a848e0f` (86,824,960 bytes).
- Hosted ISO package SHA-256: `0a7affcd603f6a4b91fb918b76106e9a12488c61cb451d8aa8985c79c03c1ab9` (42,867,520 bytes); expanding it reproduces the final ISO hash above.
- Final USB image SHA-256: `ba0f9735d02ced7882506a32e6e4c5e5b885966290f681cc279da13f25e836cd` (2,182,107,136 bytes).
- Hosted USB package SHA-256: `2a4e45236791d6167ac07834e388c8698531275f3c161d9cb453c2eea6fe3ec7` (60,386,464 bytes); this is the hash in `SHA256SUMS` for the downloadable `.img.gz`.
- Rebuilt Windows wizard after layout fix SHA-256: `cea7e96016611cf6aff8a4ed6459745a300f8b850ed27e6e712ace7192e80a2a` (25,600 bytes).
- UEFI installation matrix: ISO selected-partition, ISO whole-disk, USB selected-partition, and USB whole-disk each completed. The selected ISO install preserved the pre-existing EFI partition marker and separate NTFS data partition; the USB selected-partition case also completed. Logs and `install-result.txt` files are under `/home/goldenghostog/uefi-validation-20260925/matrix/` on the test VM.
- All four resulting installed disks were booted without ISO/USB installer media. The selected ISO install reached the Core dashboard; the selected USB install also reached the dashboard in noVNC. The USB whole-disk installed boot reached the same dashboard. The guest showed a normal user session; terminal interaction confirmed `home` UID 1000 and the installed root filesystem mounted read-write. Disk-only QEMU had no installer media attached.
- The base USB image compresses to 60,386,464 bytes. `coreOS.exe` downloads the `.img.gz`, verifies its checksum from `SHA256SUMS`, expands it locally, and then prepares the raw image for USB writing. The Windows wizard rebuild completed successfully.
- Public release verification passed after upload: the live compressed ISO, compressed USB image, and rebuilt Windows wizard were downloaded and matched their corresponding SHA-256 entries in the live `SHA256SUMS`. The OS homepage now links to those files and the checksum manifest. The Windows wizard's spacing fix was rebuilt and the updated EXE and manifest were republished together.
- Not covered by this checkpoint: physical hardware, broad compatibility testing, secure boot, signed binaries, upgrade/rollback behavior, and production readiness. The candidate is for early user dashboard feedback only.

## Published candidate files

The current public `SHA256SUMS` matches the downloaded release files:

- `bitCoreos.iso.gz`: `0a7affcd603f6a4b91fb918b76106e9a12488c61cb451d8aa8985c79c03c1ab9` (42,867,520 bytes).
- `bit-os-core-0.2-installer.img.gz`: `2a4e45236791d6167ac07834e388c8698531275f3c161d9cb453c2eea6fe3ec7` (60,386,464 bytes).
- `coreOS.exe`: `cea7e96016611cf6aff8a4ed6459745a300f8b850ed27e6e712ace7192e80a2a` (25,600 bytes).

The release remains a test candidate because validation used QEMU software emulation and does not establish physical-hardware compatibility or Secure Boot support.

## Source checks completed

- Core configuration, identity, UEFI artifact names and installer entry are tracked separately from other Beyond Imagination OS editions.
- The installer is UEFI-only and requires explicit target input. Selected-partition mode preserves the existing EFI System Partition; whole-disk mode requires an exact destructive confirmation.
- Installer startup is connected to `/dev/console`. The current USB GRUB installer entry selects `console=tty1`; serial interaction must be verified separately and is not yet a passed check.
- Portable Python tooling compiles successfully. The post-build shell test requires a functioning Linux/POSIX shell and must be rerun on the build VM.

## Still required

### Google Cloud checkpoint (2026-09-22)

- The Debian test host has QEMU and OVMF installed, but `/dev/kvm` is unavailable; the smoke tests below used bounded software emulation.
- The Core checkout is present at `/home/goldenghostog/Beyond_OS/beyond-os-desktop/core` on VM `bit-os-core-test-a`. Installer output is present in `out/installer-output/images`.
- `tests/post-build-test.py` passed on the Linux host: `PASS: target identity, single display startup, and welcome document`.
- The current installer manifest reports ISO SHA-256 `63586535422c959c38f2acea8261513497649757e2997ac51263c031e5ec9ef5` and USB SHA-256 `356fd963c02ccdc68ec5edc3b8c390b8ff45b382c3a5855c5fd9d8de6496356d`.
- Revalidation of the published SHA-256 manifest and ISO currently returns HTTP 500 after retries. The links and checksums above are historical candidate evidence, not a current availability guarantee.
- The local QEMU launcher now uses Buildroot's `rootfs.ext2` output filename. Both UEFI media smoke checks are now recorded below; both disposable-disk install modes, installed-system reboot, graphical session, network, input and shutdown remain pending.

### Google Cloud UEFI checkpoint (2026-09-22)

- The previous candidate ISO and USB image both reached the Core GRUB menu and kernel handoff under OVMF. These results must be repeated for the rebuilt v0.2 artifacts.
- Selected-partition installation, whole-disk installation, reboot without media, graphical session, network, input and shutdown have not passed. Do not use this candidate as the tested base for Cyber or the later editions yet.

### Google Cloud noVNC checkpoint (2026-09-22)

- The Debian test VM has `websockify` and `novnc` installed from its package repositories.
- QEMU VNC is listening on `127.0.0.1:5900` and websockify/noVNC is listening on `127.0.0.1:6080`; neither port is publicly bound.
- An authenticated Cloud Shell SSH tunnel exposed port 6080 to the Cloud Shell Web Preview. The noVNC client loaded successfully and connected to a 1280×800 canvas.
- The VM checkout predates the repository's `cloud/` harness scripts, so this smoke test used the equivalent direct QEMU/websockify commands. The guest kernel is visible through noVNC after adding the required `virtio-vga` device, but the frame then goes black; the Core splash and Openbox/SDL desktop have not passed. Mouse, keyboard, network and shutdown acceptance remain pending.

Run the following on an x86-64 Linux build host and retain the resulting logs and hashes:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
```

Then complete the UEFI and disposable-disk checks in `RELEASE.md`, including the installed-system reboot and graphical-session checks. Only after those checks and artifact checksum publication may Core be offered for download.

### Google Cloud resume checkpoint (2026-09-24)

- VM `bit-os-core-test-a` in project `project-79ff0164-14f2-4be2-ba5`, zone `northamerica-northeast2-a`, was started and is currently running (e2-standard-4, 16 GiB RAM).
- Serial port 1 shows the Debian 13 test host booting its `graphical.target`; the system starts `getty@tty1` and `serial-getty@ttyS0`. This verifies the host VM boot only, not the Core guest.
- The attached `bit-os-core-test-target` disk remains present as a 20 GiB standard persistent disk.
- The browser SSH launch did not open an interactive session in this run. Resume with browser SSH or Cloud Shell, then inspect the Core build artifacts and continue the noVNC/guest graphical-session checks. Keep VNC/noVNC bound to loopback as specified above.

### Google Cloud Core v0.2 candidate smoke test (2026-09-24)

- The remote Core checkout contains uncommitted changes to `board/x86_64/post-build.sh` and `board/x86_64/post-image-uefi.sh`, plus untracked `tests/uefi/`. Preserve these changes when resuming.
- Fresh outputs are present: `bitCoreos.iso` (83 MiB), `bit-os-core-0.2-installer.img` (2.1 GiB), and `rootfs.ext2` (2.0 GiB). `tests/post-build-test.py` passed on the VM. `sha256sum -c out/installer-output/images/SHA256SUMS` passed for all three artifacts.
- Manifest hashes: installer `7526d3b6190729ba19ab7dbaead914115b35019e03eee3dd1b070121c92468ad`; ISO `01d81362d2c5988972c086dd8b3754f528bb6410eb01b5bd75dec8dfff2916b9`; root filesystem `28a4e2a819898b3d019df39475ae5067186c41e901d91278e9114c50d849b405`.
- The disposable QEMU guest booted in software emulation (`/dev/kvm` unavailable). Its serial log shows Buildroot services starting and DHCP lease `10.0.2.15`; DNS `10.0.2.3` was assigned but resolution was not checked.
- QEMU VNC and websockify/noVNC listened on `127.0.0.1:5900` and `127.0.0.1:6080`. An SSH tunnel through Cloud Shell exposed the viewer on local port 8080; the viewer loaded and connected.
- **Graphical gate failed:** noVNC displayed the Core v0.2 splash, then the main VT showed a blank dark screen instead of the desktop. Ctrl+Alt+F2 switched to the splash VT and Ctrl+Alt+F1 returned to the blank screen, confirming keyboard/virtual-terminal delivery but not usable desktop input. The Core desktop and mouse acceptance remain pending.
- Selected-partition and whole-disk installation, installed-system reboot, DNS resolution, usable desktop, guest input, and clean shutdown remain unverified. Do not mark the graphical or release gates passed.

### Desktop boot and header fixes (2026-09-24 Pacific / 2026-09-25 UTC)

- Host: `bit-os-core-test-a`, project `project-79ff0164-14f2-4be2-ba5`, zone `northamerica-northeast2-a`. Source: `/home/goldenghostog/Beyond_OS/beyond-os-desktop/core`. The host still uses TCG because `/dev/kvm` is unavailable.
- Diagnosed Xorg's fatal `fbdevHWEnterVT: symbol not found` error. Explicitly loading `fbdevhw` and `shadow` before the framebuffer driver allows Xorg to start. BusyBox `start-stop-daemon -O` now retains the child process's startup output; shell redirection alone was discarded during daemonization. The launcher keeps the delimiter between daemon options and xinit arguments.
- A subsequent cold boot exposed a window-manager startup race: the desktop could stay black until another window opened. The session now starts the desktop through Openbox's `--startup` hook, after Openbox has initialized window management. A fresh normal boot of the final rebuilt image reached the desktop without console intervention.
- Fixed the top-left overlap by positioning `CORE EDITION v0.2` after the rendered width of `Beyond Imagination OS`, with a 24-pixel gap. Visually confirmed in the final guest.
- `bash build.sh installer` completed, including compilation with `-Wall -Wextra -Werror`. The configuration check retained all 68 requested settings with root login and SSH disabled. `python3 tests/post-build-test.py` and all three `sha256sum -c SHA256SUMS` checks passed.
- Final artifact hashes (these supersede the earlier hashes above):
  - `bit-os-core-0.2-installer.img`: `edc81e05be4725dbcdca5ed3310e13cc289ed551c38fe98675972cc275e9b071`
  - `bitCoreos.iso`: `ff77e99c0591450436060e3fc249302476c62705d5e70b47c4676d5bd8a3c910`
  - `rootfs.ext2`: `29ab09f7b85f0f58838273f6ee2e948cd870c22a037b817d9f5daa6d5c179050`
- Runtime observations: desktop and terminal run as `home` (UID/GID 1000); mouse launches Terminal and navigates Files; individual keyboard events execute `id`; Files opens `Documents/Welcome.txt`; Escape returns to the home screen. The final image's file browser, welcome document, keyboard navigation, and corrected header were verified through noVNC.
- In the diagnostic guest with the same session changes, `nslookup example.com 10.0.2.3` resolved A/AAAA records and `wget http://example.com` resolved the hostname through the default resolver and downloaded 559 bytes as `home`. Bare BusyBox `nslookup` rejects the DHCP-generated inline `# eth0` comment; this utility issue remains, although application DNS works. Bulk automated noVNC typing dropped characters, so keyboard validation used individual key events.
- The diagnostic guest used normal `/sbin/init` startup plus a temporary serial shell in its disposable overlay. Guest `poweroff` caused QEMU to exit cleanly. The final image was rebuilt from source afterward and booted normally without that diagnostic shell or an `init=/bin/sh` override.
- Evidence is retained on the host under `out/validation/2026-09-25-desktop/`: build/verification log, runtime checks, shutdown check, final boot log, exact QEMU arguments, and manifest. The final guest is disposable (`-snapshot`), with 2 GiB RAM, 2 vCPUs, virtio VGA at 1280x800, USB keyboard/tablet, and user-mode networking. VNC/noVNC remain on loopback behind the existing IAP SSH tunnel.
- Resume at the UEFI ISO/USB and disposable-disk installation gates. This direct-kernel desktop smoke test does not establish selected-partition installation, whole-disk installation, or reboot of an installed disk. Final artifacts have not been republished. Preserve the remote hook changes and untracked `tests/uefi/` from earlier work.

