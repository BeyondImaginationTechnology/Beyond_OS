# BIT OS Core v.02 VM gate record

Run `run-installer-qemu.sh` with a freshly built installer image and a disposable
secondary disk. Record the date, host, QEMU command, media SHA-256, and result.
Do not mark a gate passed from a boot-menu screenshot alone.

- [ ] Selected-partition install preserves the existing EFI System Partition.
- [ ] Whole-disk install works on the disposable target and leaves the source media unchanged.
- [ ] Installed system reboots without installer media.
- [ ] Graphical session reaches the BIT OS Core v.02 desktop.
- [ ] Keyboard and mouse input work in the graphical session and terminal.
- [ ] Network obtains connectivity and can resolve a host.
- [ ] Shutdown powers off or cleanly halts the guest.

The release remains a test candidate until every item has evidence attached.
