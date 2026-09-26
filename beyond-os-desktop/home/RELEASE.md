# BIT OS Home v0.1 acceptance gates

The current work is `home-0.1-dev.1`. Passing source checks is not a successful boot.

## Foundation acceptance

- [ ] Complete a clean build on Linux using the locked source archive.
- [ ] Record kernel/rootfs hashes, resolved config, build log and dependency notices.
- [ ] QEMU cold boot displays the Beyond splash and reaches the Home desktop.
- [ ] UEFI QEMU boot reaches both the Home and installer boot-menu entries.
- [ ] Installer supports both selected-partition and selected-whole-disk paths; refuses its own USB, mounted targets, invalid EFI partitions, and confirmation mismatches.
- [ ] Installer writes to a disposable QEMU target partition and creates a separate UEFI entry without replacing another entry.
- [ ] Reboot from the QEMU target reaches Home with the USB removed.
- [ ] No parent-distribution branding in boot, desktop or system identity.
- [ ] Mouse, keyboard, focus switching and display scaling work.
- [ ] Terminal confirms UID 1000; password login for root remains locked.
- [ ] Files handles empty/unreadable folders, long names and non-text files.
- [ ] Notes retains text after save/reopen and a persistent-image reboot.
- [ ] Notes save failure reports an error and preserves unsaved text.
- [ ] Browser opens as `home`, loads an HTTPS page, validates certificates, and
      uses its process sandbox without disabling it.
- [ ] Browser keyboard/mouse navigation, download location, and a representative
      media page work in the installed QEMU guest.
- [ ] Media opens local MP3, Ogg, WAV, MP4, and WebM fixtures from `~/Media`;
      video renders and audio reaches the QEMU sound device.
- [ ] Home dashboard tiles, taskbar, keyboard navigation, window switching,
      Files and Notes remain usable with Browser and Media installed.
- [ ] X.Org listens only locally and requires the session cookie.
- [ ] Headless/failing-display boot leaves readable serial diagnostics.

## Home 1.0 product gates

- [ ] Modern browser with sandboxing, certificates, security updates and web QA.
- [ ] Beyond TV and other web integrations verified using the actual browser.
- [ ] Audio, video decoding, network settings and removable-media workflows.
- [ ] Accessible desktop navigation, text editing and assistive technology.
- [ ] Account creation, login, screen lock and privacy defaults.
- [ ] Signed updates with rollback and recovery from interruption.
- [ ] Branded UEFI boot, Live USB and graphical installer; explicit disk selection.
- [ ] Encryption setup/unlock/recovery; Secure Boot strategy and signing.
- [ ] Suspend, resume, power controls and representative hardware testing.
- [ ] Installer and firmware boot-screen branding audit.
- [ ] Third-party licensing/source distribution review and published notices.
- [ ] Release support policy, recovery instructions and verified downloads.

Never label an artifact as a stable 1.0 release until its applicable gates pass.
