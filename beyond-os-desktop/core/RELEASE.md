# BIT OS Core v0.2 release gates

Current work: `core-0.2`. Source creation is not a release.

Run the following host-side checks after a clean build. They produce deterministic
failures when the generated config, release files, or required test tools are
missing:

```sh
python3 tools/verify-config.py configs/beyond_core_x86_64_defconfig out/output/.config
python3 tests/post-build-test.py
sh tests/verify-release-artifacts.sh
```

The install, reboot, graphical-session, input, network, and shutdown gates must
be run on a disposable UEFI VM with a second virtual disk. Record the VM command,
disk image hash, and observed result for each gate before checking it off.
`tests/run-installer-qemu.sh` creates that secondary disk as a temporary qcow2
and boots the installer with OVMF; it removes the disk when QEMU exits unless
`BEYOND_KEEP_DISK=1` is explicitly set.
Record each result in `tests/VM-GATES.md`; a boot-menu handoff is not evidence
of an installed graphical session.
For the HTTP 500 blocker, follow `RELEASE-HOST.md` and rerun
`tests/verify-release-artifacts.sh` from an external network.

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

Do not label, market, or support an ISO, USB image, or Windows installer profile as stable BIT OS Core v0.2 until the applicable gates pass. Public test-candidate downloads must remain clearly labelled and accompanied by their SHA-256 manifest.
