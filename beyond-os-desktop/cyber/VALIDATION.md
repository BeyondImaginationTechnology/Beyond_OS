# BIT OS Cyber 0.1 validation — 2026-09-30

Status: **Development preview packaged for publication. Stable release gates remain open.**

## Build

- Build host: Google Compute Engine `bit-os-core-test-a`, x86-64 Linux, project `project-79ff0164-14f2-4be2-ba5`, zone `northamerica-northeast2-a`.
- Isolated checkout: `/home/goldenghostog/cyber-v0.1-work/cyber`.
- `BR2_JLEVEL=2 bash build.sh installer` completed successfully. Final build log: `/home/goldenghostog/cyber-v0.1-lsblk-build.log`.
- `tools/verify-config.py` passed: 70 requested settings retained; root password login and SSH disabled.
- Buildroot installer output: `out/installer-output/images`. Its original `SHA256SUMS` verified with `sha256sum -c`:

| Raw image | SHA-256 |
| --- | --- |
| `bit-os-cyber-0.1-installer.img` | `8a4b204f0454f4ef070865747cb023e6b0bda5dd1ff0ab73e7a67a49dede7aa3` |
| `bitCyberos.iso` | `9a091201bfc54de40cfa2c3f7802ade6f78b32e706c7538655533aabe8b9ec27` |
| `rootfs.ext2` | `e7f4ada3f4f7159ca180c9d75c5f9ee1a762946d8d9bbbcb5d4220f009de7764` |

Candidate download archives are staged locally in `.local/cyber-v0.1/release-candidate/` (ignored by Git). `gzip -t` and `sha256sum -c` passed on the build host; local downloaded files also matched the compressed-file manifest:

| Download archive | Size | SHA-256 |
| --- | ---: | --- |
| `bitCyberos.iso.gz` | 41,866,165 bytes | `51f3f3d8fc2ae5f728049c8b1e4840d61015ad6b0b2a77d73ca8dd5c167bf163` |
| `bit-os-cyber-0.1-installer.img.gz` | 59,601,150 bytes | `e8a868005d0dbc9a1d8dcffbe637657cbb7fbfa2b456f3d836f2b30ce27168e6` |

`SHA256SUMS` hashes the compressed downloads. `SHA256SUMS.raw` preserves the Buildroot raw-image hashes.

## UEFI boot and installation

- The ISO and GPT USB image displayed both **Try BIT OS Cyber Edition 0.1** and **Install BIT OS Cyber Edition 0.1** in QEMU with OVMF UEFI and software emulation.
- The GPT image's Try entry reached the live Cyber desktop. Its Install entry refused to erase its own `/dev/vda` installer disk with `Installer stopped: the installer USB disk cannot be erased`. The latter check used the final GPT image after the `lsblk -dn` fix.
- The final ISO's Install entry completed the explicit whole-disk workflow on a fresh disposable 6 GiB qcow2 `/dev/vda`, including the exact `ERASE /dev/vda` confirmation.
- After removing the ISO, OVMF loaded the installed disk's `BIT OS Cyber` NVRAM entry at `\EFI\BITOS\bootx64.efi`; GRUB loaded the system, and the Cyber desktop appeared. The serial log showed nftables rules loading and DHCP obtaining `10.0.2.15`. Evidence: `/tmp/cyber-v01-iso-final-installed-serial.log` on the VM and local screenshot `.local/cyber-v0.1/iso-final-installed.png`.
- A later installer build completed the selected-partition workflow to `/dev/vda3` with the EFI system partition on GPT partition 2. The pre-existing EFI marker and partition 1 label remained intact. Its new GRUB configuration and kernel were present on the EFI partition. The installed disk was not cold-booted without the ISO after this change.

## Fixes found during installation testing

- Include `wipefs` and GNU tar in the installer image.
- Avoid creating live-root mount points after the ISO has remounted its root read-only.
- Restore runtime directories in the installed root and boot it read/write.
- Copy the kernel to the installed EFI system partition, mount `efivarfs` before registering the boot entry, and provide a standard UEFI fallback path on a newly formatted ESP.
- Use the correct single-backslash EFI path for the NVRAM entry.
- Limit disk-type detection to the selected block device with `lsblk -dn`, so a USB disk's child partitions cannot mask the self-erase guard.
- Refresh the target's GRUB template in the post-build hook and include serial output for boot diagnostics.
- Use a separate installed-system GRUB template that finds the selected EFI partition by filesystem UUID and stores the kernel under `EFI/BITOS`; this avoids assuming the EFI partition is GPT partition 1 or overwriting an ESP-root kernel file.
- Read the EFI GPT type with `lsblk`; `blkid -s PART_ENTRY_TYPE` returned no value for a valid EFI partition in the selected-partition test.

## Remaining release gates

The clean system build was stopped at the user's request after successful installer-image validation. The standalone system-image cold boot, final selected-partition disk-only boot, remaining installer rejection cases, persistence after reboot, detailed firewall checks, physical-device checks, legal/source review, signed Windows installer, and public download checks remain open. See `RELEASE.md`. Secure Boot, full-disk encryption, and signed in-system updates are not implemented in this candidate.

The VM also hosts active Home testing sessions. Do not stop it as part of Cyber cleanup. The Cyber QEMU session used for the final installed-disk check was stopped after the result was recorded.
