# BIT OS Core v0.2 VM gate record

Run `run-installer-qemu.sh` with a freshly built installer image and a disposable
secondary disk. Record the date, host, QEMU command, media SHA-256, and result.
Do not mark a gate passed from a boot-menu screenshot alone.

Current installed-system record: **the UEFI ISO/USB installation and no-media
boot gates passed on disposable QEMU disks on 2026-09-25 UTC.** This is software
emulation evidence, not physical-hardware coverage or a stable-release claim.

- [x] **Selected-partition install:** ISO and USB workflows completed on disposable target layouts; pre-existing EFI and separate data-partition preservation was checked.
- [x] **Whole-disk install:** ISO and USB workflows completed on disposable secondary disks.
- [x] **Installed reboot:** installed disks booted without installer media; all four combinations reached the OS boot path.
- [x] **Graphical session:** installed ISO-selected, ISO-whole, USB-selected, and USB-whole boots reached the Core dashboard in QEMU.
- [x] **Input:** mouse opened Terminal; keyboard terminal commands confirmed `home` UID 1000 and an installed read-write root filesystem.
- [x] **Network:** earlier disposable guest checks obtained DHCP/DNS configuration and resolved/fetched an HTTP page.
- [x] **Shutdown:** earlier disposable guest poweroff exited QEMU cleanly.

The release remains a test candidate; public artifact upload and hash verification
are tracked separately in `../VALIDATION.md`.

## UEFI installer matrix — 2026-09-25 UTC

All runs used OVMF on QEMU TCG with disposable image files on
`bit-os-core-test-a` (`project-79ff0164-14f2-4be2-ba5`, zone
`northamerica-northeast2-a`). No physical host disk was attached to a guest.
Per-case install markers and logs are retained under
`/home/goldenghostog/uefi-validation-20260925/matrix/` on that VM.

| Installer | Disk mode | Install | Boot without media | Dashboard |
| --- | --- | --- | --- | --- |
| ISO | Selected partition | Pass | Pass | Pass |
| ISO | Whole disk | Pass | Pass | Pass |
| USB | Selected partition | Pass | Pass | Pass |
| USB | Whole disk | Pass | Pass | Pass |

For the selected ISO install, the pre-existing EFI marker and separate NTFS
data partition remained intact. The installed USB-selected boot also confirmed
the user session, keyboard interaction in Terminal, and a read-write installed
root filesystem. Public release-host bytes still require separate verification.

## Direct-kernel desktop smoke test — 2026-09-25 UTC

The rebuilt `rootfs.ext2` (SHA-256
`29ab09f7b85f0f58838273f6ee2e948cd870c22a037b817d9f5daa6d5c179050`)
cold-booted to the corrected desktop on `bit-os-core-test-a` using QEMU q35,
TCG, 2 GiB RAM, 2 vCPUs, virtio VGA, USB keyboard/tablet, and `-snapshot`.
The guest used the normal init path, with no diagnostic shell override.

- Observed: automatic desktop startup, corrected header spacing, mouse-driven
  Files navigation, readable `Documents/Welcome.txt`, and Escape navigation.
- The preceding disposable diagnostic boot with the same session fixes verified
  the non-root `home` desktop and terminal, individual keyboard events, DNS/HTTP
  access, and guest-initiated poweroff with QEMU exit.
- Build, configuration, post-build, and artifact checksum checks passed.
- See `../VALIDATION.md` for the full manifest, runtime limitations, exact host
  location of logs and QEMU arguments, and the remaining UEFI/installation work.

These observations do not check off the installed-system gates above.
