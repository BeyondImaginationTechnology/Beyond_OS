# Beyond Imagination OS Core validation

Status: **the BIT OS Core v0.2 candidate is being rebuilt and remains blocked until the fresh artifacts pass every release gate.**

## Candidate evidence

The remote build produced these artifacts in the installer output directory:

- `bitCoreos.iso` — 81 MiB (filesystem listing on the test VM).
- `bit-os-core-0.2-installer.img` — fresh size and digest pending rebuild.

The release-host URLs are retained as candidate endpoints, but the current VM artifacts have not yet been confirmed rehosted there:

- `https://os.beyondimagination.co.technology/releases/core/0.2/bitCoreos.iso`
- SHA-256: pending fresh v0.2 rebuild.
- `https://os.beyondimagination.co.technology/releases/core/0.2/bit-os-core-0.2-installer.img`
- SHA-256: pending fresh v0.2 rebuild.
- `https://os.beyondimagination.co.technology/releases/core/0.2/SHA256SUMS`

Treat the VM artifacts as test candidates only until the release-host files and every gate below are revalidated.

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

