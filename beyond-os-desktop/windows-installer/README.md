# BIT OS Windows Installer

`BITOSInstaller.exe` is a Windows USB-creation companion for all seven BIT OS 1.0 flavours: Home, Core, Creator, Academy, Cyber, Sentinel, and Gaming.

It downloads the selected GUID Partition Table (GPT) installer image, verifies its SHA-256 checksum, shows USB disks by physical-disk number, and requires typing `ERASE <number>` before writing. The computer then reboots into the BIT OS installer, which performs the partitioning and installation outside Windows.

Core is available as a test candidate. The other six entries remain clearly marked as coming soon until their artifacts, SHA-256 manifests, and applicable release gates have been published.

Build on Windows:

```powershell
.\build.ps1
```

The output is `dist\BITOSInstaller.exe`. This build is not code-signed; a production release requires an Authenticode certificate and post-build signing.
