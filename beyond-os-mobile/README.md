# Beyond OS Mobile 0.1 — AOSP Community Track

This is the Android operating-system track. It starts as an AOSP-derived device project, not as a replacement for an Android app.

## 0.1 scope

- One community-supported, unlockable device target selected before code is forked.
- AOSP or LineageOS base pinned to an upstream security-maintained release.
- Beyond launcher, first-boot setup, Beyond ID account handoff, and the Beyond OS Web app.
- No bundled Google Mobile Services. A separate certified build would require Google approval and licensing.

## Milestone gates

1. Pick a device with public kernel sources, recoverable bootloader, active LineageOS support, and a healthy community.
2. Build the unmodified base and prove radio, camera, encryption, OTA recovery, and emergency calling.
3. Add the Beyond launcher and setup wizard as independently testable packages.
4. Publish source, reproducible build instructions, signing-key policy, vulnerability disclosure process, and rollback guidance before community testing.

`BeyondOSAndroid/` is the companion/launcher prototype used to validate the experience before it is promoted into the AOSP tree.
