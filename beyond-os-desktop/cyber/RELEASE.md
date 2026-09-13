# BIT OS Cyber Edition 1.0 release gates

Current work: `cyber-dev.1`. Source completion is not a stable release.

## Build and provenance

- [ ] Resolve both Cyber Buildroot configurations against the locked upstream source.
- [ ] Complete clean system and installer builds on an x86-64 Linux host.
- [ ] Retain the resolved configurations, build logs, SHA-256 manifest, and Buildroot legal information.
- [ ] Confirm that the ISO, GUID Partition Table (GPT) USB image, and root filesystem match the published manifest.
- [ ] Build and Authenticode-sign `BITOSInstaller.exe`; retain its SHA-256 hash and signing evidence.

## Boot and installation

- [ ] Cold-boot the system image to the Cyber desktop as the non-root user.
- [ ] Boot `bitCyberos.iso` with OVMF UEFI and verify both Try Cyber and Install Cyber entries.
- [ ] Boot the GUID Partition Table (GPT) USB image with OVMF UEFI and verify both menu entries.
- [ ] Install to a disposable virtual disk using the selected-partition workflow.
- [ ] Install to a disposable virtual disk using the explicit whole-disk workflow.
- [ ] Confirm that the installer rejects its own USB, mounted targets, undersized targets, invalid EFI partitions, and mismatched confirmation text.
- [ ] Reboot the installed disk without installer media and reach the Cyber desktop.

## Product and security behavior

- [ ] Confirm UID 1000 for the desktop session; root password login and SSH remain disabled.
- [ ] Verify the firewall allows loopback, established traffic, and DHCP replies while denying unsolicited inbound and forwarded traffic.
- [ ] Verify the evidence workspace persists after reboot and reports storage failures safely.
- [ ] Verify keyboard, mouse, display, terminal, network, shutdown, and readable serial diagnostics.
- [ ] Confirm no parent-distribution branding appears in boot, desktop, release metadata, or system identity.
- [ ] Complete third-party licensing/source-distribution review and publish notices.
- [ ] Publish recovery instructions, known limitations, support policy, and verified download links.

## Deferred capabilities that must be disclosed

Secure Boot, full-disk encryption, and signed in-system updates are not implemented in the current candidate. They must either be completed before a stable 1.0 release or be prominently documented as unsupported with an approved security rationale.

Never label an artifact as stable BIT OS Cyber 1.0 until every applicable gate above passes.
