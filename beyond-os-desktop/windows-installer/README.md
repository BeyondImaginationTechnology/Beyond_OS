# BIT OS Windows Installer

`BITOSInstaller.exe` is a Windows USB-creation companion for BIT OS editions. Core and Home v0.2, Creator v0.1, Academy v0.1, and Cyber v0.1 are currently published as development previews or test candidates.

It downloads the selected GUID Partition Table (GPT) installer image, verifies its SHA-256 checksum, shows USB disks by physical-disk number, and requires typing `ERASE <number>` before writing. The computer then reboots into the BIT OS installer, which performs the partitioning and installation outside Windows.

Sentinel and Gaming remain clearly marked as coming soon until their artifacts, SHA-256 manifests, and applicable release gates have been published.

Build on Windows:

```powershell
.\build.ps1
```

The build produces `dist\BITOSInstaller.exe` plus edition-named copies: `coreOS.exe`, `homeOS.exe`, `creatorOS.exe`, `academyOS.exe`, and `cyberOS.exe`. These builds are not code-signed; a production release requires an Authenticode certificate and post-build signing.
