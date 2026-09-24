# BIT OS Core v.02 VM gate record

Run `run-installer-qemu.sh` with a freshly built installer image and a disposable
secondary disk. Record the date, host, QEMU command, media SHA-256, and result.
Do not mark a gate passed from a boot-menu screenshot alone.

Current record: **not executed**. The available VM has only its mounted boot
disk, so no destructive install test was attempted. The release host is being
deployed separately; these entries remain pending until a disposable target is
attached.

- [ ] **Pending — selected-partition install:** requires a disposable Linux partition and separate EFI partition.
- [ ] **Pending — whole-disk install:** requires a disposable secondary disk; boot disk must remain untouched.
- [ ] **Pending — installed reboot:** requires completion of one install gate and removal of installer media.
- [ ] **Pending — graphical session:** requires a successful installed boot to the BIT OS Core v.02 desktop.
- [ ] **Pending — input:** requires keyboard and mouse interaction in the graphical session and terminal.
- [ ] **Pending — network:** requires guest connectivity and DNS evidence.
- [ ] **Pending — shutdown:** requires a clean guest poweroff/halt observation.

The release remains a test candidate until every item has evidence attached.
