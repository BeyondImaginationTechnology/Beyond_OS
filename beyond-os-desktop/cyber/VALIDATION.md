# BIT OS Cyber 0.1 validation — 2026-09-26

Status: **0.1 identity and post-build preflight pass; the installer build is running on the GCP test VM. UEFI boot and public download availability remain pending.**

## Cyber 0.1 build checkpoint — 2026-09-26

- Cyber product identity, on-screen version, installer name, and release paths now use 0.1 / `cyber-0.1-dev.1`.
- `python3 tests/post-build-test.py` passed on `bit-os-core-test-a` before the installer build.
- The installer build is running with `BR2_JLEVEL=2` in `/home/goldenghostog/cyber-v0.1-work/cyber/out/installer-output`; log: `/home/goldenghostog/cyber-v0.1-build.log`.
- Buildroot host GCC is compiling. The only messages found so far are optional missing `makeinfo` documentation notices; no build failure has occurred.
- Public files are intended for `/releases/cyber/0.1/` as compressed ISO and GPT image with a SHA-256 manifest. The host currently serves the Core release files but has no known upload credentials from this workspace.

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
/releases/cyber/0.1/bitCyberos.iso.gz
/releases/cyber/0.1/bit-os-cyber-0.1-installer.img.gz
/releases/cyber/0.1/SHA256SUMS
```

The Windows USB creator is not part of this initial 0.1 candidate download set.
Do not enable the Cyber profile in `windows-installer/Program.cs` until its
unsigned status is resolved and the image and manifest return successfully
from the public URLs.
