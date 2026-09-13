# BIT OS Cyber validation — 2026-09-13

Status: **source candidate repaired and locally preflighted; Linux build, UEFI boot, installation, and security behavior remain unverified.**

## Completed in this workspace

- Cyber uses the pinned Buildroot 2026.02.3 source archive and recorded SHA-256.
- Static configuration verification rejects dropped settings, root password login, Dropbear, and OpenSSH.
- The installer requires UEFI, explicit target selection, and exact confirmation text. It refuses its own USB, mounted targets, invalid EFI partitions, and undersized destinations.
- The post-build hook now creates installer mount points before the live ISO remounts `/` read-only. This repairs a failure path where the ISO installer could not create `/mnt/bit-*` after the remount.
- The post-build test now covers Cyber identity, display startup replacement, firewall startup, the evidence workspace, and installer mount-point creation.
- The shared Windows USB creator builds successfully with the .NET Framework compiler and requires administrator elevation. Its release URLs now use the canonical `os.beyondimagination.co.technology/releases/` paths.
- The public OS page now uses the same canonical Cyber release directory and only exposes download buttons for files present on the release host.
- The known Core candidate manifest is reachable on the canonical release host. The Cyber release directory was not available during this audit.

## Still required on a Linux build host

```sh
cd beyond-os-desktop/cyber
bash build.sh configure
bash build.sh build
bash build.sh installer
python3 tests/post-build-test.py
bash build.sh legal-info
```

Retain the build logs and the complete `output/images` and `installer-output/images` metadata. Then perform every boot, disposable-disk, firewall, persistence, and hardware check in `RELEASE.md`.

## Publication layout

After the applicable release gates pass, publish the verified candidate files together:

```text
/releases/cyber/1.0/bitCyberos.iso
/releases/cyber/1.0/bit-os-cyber-1.0-installer.img
/releases/cyber/1.0/SHA256SUMS
/releases/cyber/1.0/BITOSInstaller.exe
```

Do not set the Cyber profile in `windows-installer/Program.cs` to `Available = true` until the image and manifest return successfully from those public URLs.
