# BIT OS Windows Installer

`BITOSInstaller.exe` is a Windows USB-creation companion for BIT OS Home 1.0, Cyber 1.0, and Core 1.0.

It downloads the selected GPT installer image, verifies its SHA-256 checksum, shows USB disks by physical-disk number, and requires typing `ERASE <number>` before writing. The computer then reboots into the BIT OS installer, which performs the partitioning and installation outside Windows.

All edition download endpoints remain unavailable until their artifacts, SHA-256 manifests, and release gates have been published.

Build on Windows:

```powershell
.\build.ps1
```

The output is `dist\BITOSInstaller.exe`. This build is not code-signed; a production release requires an Authenticode certificate and post-build signing.
