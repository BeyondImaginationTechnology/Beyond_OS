# BIT OS Core Edition 1.0 release gates

Current work: `core-dev.1`. Source creation is not a release.

- [ ] Resolve the Core Buildroot configuration against the locked upstream source.
- [ ] Complete a clean Linux build and record hashes, configuration, logs and notices.
- [ ] Cold-boot the QEMU image to the Core session as the non-root user.
- [ ] Boot `bitCoreos.iso` in UEFI QEMU and verify both Try Core and Install Core.
- [ ] Install to a disposable virtual disk using the selected-partition workflow.
- [ ] Install to a disposable virtual disk using the explicit whole-disk workflow.
- [ ] Reboot the installed virtual disk without installer media.
- [ ] Confirm that boot, desktop, release metadata and system identity contain no parent-distribution wording.
- [ ] Verify keyboard, mouse, display, filesystem, terminal, network and shutdown behavior.
- [ ] Validate checksum verification, recovery guidance, licensing notices and support policy.

Do not publish an ISO, USB image or Windows installer profile until applicable
gates pass.