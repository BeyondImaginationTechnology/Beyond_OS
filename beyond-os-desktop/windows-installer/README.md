# BIT OS Windows Installer

`BITOSInstaller.exe` is a Windows USB-creation companion for BIT OS editions. Core and Home v0.2, and Creator, Academy, Cyber, Sentinel, and Gaming v0.1 are configured as development previews or test candidates.

It downloads the selected GUID Partition Table (GPT) installer image, verifies its SHA-256 checksum, shows USB disks by physical-disk number, and requires typing `ERASE <number>` before writing. The computer then reboots into the BIT OS installer, which performs the partitioning and installation outside Windows.

Sentinel and Gaming downloads should be enabled on the public OS page only after their installer images and SHA-256 manifests have been uploaded and verified.

Build on Windows:

```powershell
.\build.ps1
```

The build produces `dist\BITOSInstaller.exe` plus edition-named copies: `coreOS.exe`, `homeOS.exe`, `creatorOS.exe`, `academyOS.exe`, `cyberOS.exe`, `sentinelOS.exe`, and `gamingOS.exe`. These builds are not code-signed; a production release requires an Authenticode certificate and post-build signing.
