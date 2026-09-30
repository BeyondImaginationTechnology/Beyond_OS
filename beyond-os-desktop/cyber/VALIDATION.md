# BIT OS Cyber 0.1 validation — 2026-09-30

Status: **Development candidate built and booted under QEMU/OVMF. Stable release gates remain open; Cyber 0.1 is not published.**

## Build

- Build host: Google Compute Engine `bit-os-core-test-a`, x86-64 Linux, project `project-79ff0164-14f2-4be2-ba5`, zone `northamerica-northeast2-a`.
- Isolated checkout: `/home/goldenghostog/cyber-v0.1-work/cyber`.
- `BR2_JLEVEL=2 bash build.sh installer` completed successfully. Final build log: `/home/goldenghostog/cyber-v0.1-lsblk-build.log`.
- `tools/verify-config.py` passed: 70 requested settings retained; root password login and SSH disabled.
- Buildroot installer output: `out/installer-output/images`. Its original `SHA256SUMS` verified with `sha256sum -c`:

| Raw image | SHA-256 |
| --- | --- |
| `bit-os-cyber-0.1-installer.img` | `08beef578deea86e6aeec3623b5c9120017a10e818ae2010150b45cf97356a0d` |
| `bitCyberos.iso` | `49083488f79b464499ef8dab737535e9069249b77a01ab77472ad24324e96d2d` |
| `rootfs.ext2` | `1aa50f6619b06717a5a9fc0b99909f6206051102b944908e7c75d1c718f35fa9` |

Candidate download archives are staged locally in `.local/cyber-v0.1/release-candidate/` (ignored by Git). `gzip -t` and `sha256sum -c` passed on the build host; local downloaded files also matched the compressed-file manifest:

| Download archive | Size | SHA-256 |
| --- | ---: | --- |
| `bitCyberos.iso.gz` | 41,866,136 bytes | `70eb95e7aec5471b32f87d04e7aee2bd0c291a182e90bb361af952c3a83ee0e8` |
| `bit-os-cyber-0.1-installer.img.gz` | 59,601,114 bytes | `8a95de1b3d55158c0d280a312ceadf2a3e1caaa9023f8bf4743fa0a84a2ba7ad` |

`SHA256SUMS` hashes the compressed downloads. `SHA256SUMS.raw` preserves the Buildroot raw-image hashes.

## UEFI boot and installation

- The ISO and GPT USB image displayed both **Try BIT OS Cyber Edition 0.1** and **Install BIT OS Cyber Edition 0.1** in QEMU with OVMF UEFI and software emulation.
- The GPT image's Try entry reached the live Cyber desktop. Its Install entry refused to erase its own `/dev/vda` installer disk with `Installer stopped: the installer USB disk cannot be erased`. The latter check used the final GPT image after the `lsblk -dn` fix.
- The final ISO's Install entry completed the explicit whole-disk workflow on a fresh disposable 6 GiB qcow2 `/dev/vda`, including the exact `ERASE /dev/vda` confirmation.
- After removing the ISO, OVMF loaded the installed disk's `BIT OS Cyber` NVRAM entry at `\EFI\BITOS\bootx64.efi`; GRUB loaded the system, and the Cyber desktop appeared. The serial log showed nftables rules loading and DHCP obtaining `10.0.2.15`. Evidence: `/tmp/cyber-v01-iso-final-installed-serial.log` on the VM and local screenshot `.local/cyber-v0.1/iso-final-installed.png`.
- The installed disk still labels its boot menu entry **Try BIT OS Cyber Edition 0.1**. This wording should be changed before stable release.

## Fixes found during installation testing

- Include `wipefs` and GNU tar in the installer image.
- Avoid creating live-root mount points after the ISO has remounted its root read-only.
- Restore runtime directories in the installed root and boot it read/write.
- Copy the kernel to the installed EFI system partition, mount `efivarfs` before registering the boot entry, and provide a standard UEFI fallback path on a newly formatted ESP.
- Use the correct single-backslash EFI path for the NVRAM entry.
- Limit disk-type detection to the selected block device with `lsblk -dn`, so a USB disk's child partitions cannot mask the self-erase guard.
- Refresh the target's GRUB template in the post-build hook and include serial output for boot diagnostics.

## Remaining release gates

The clean system build and standalone system-image cold boot, selected-partition install, remaining installer rejection cases, persistence after reboot, detailed firewall checks, physical-device checks, legal/source review, signed Windows installer, and public download checks are still open. See `RELEASE.md`. Secure Boot, full-disk encryption, and signed in-system updates are not implemented in this candidate.

The VM also hosts active Home testing sessions. Do not stop it as part of Cyber cleanup. The Cyber QEMU session used for the final installed-disk check was stopped after the result was recorded.
