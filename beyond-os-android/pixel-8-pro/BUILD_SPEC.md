# Beyond OS Android ROM — Pixel 8 Pro

This is the initial Beyond OS Beta build target for the Google Pixel 8 Pro.

| Setting | Value |
| --- | --- |
| Device | Pixel 8 Pro |
| Device codename | husky |
| SoC family | Tensor G3 (zuma) |
| Android base | Android 15 stable |
| Release line | Android 15 QPR1 |
| Build variant | userdebug |
| Product target | aosp_husky |
| Kernel family | android-gs-shusky-5.15-android15-qpr1 |

## Required artifacts

The release job must keep the matching factory/vendor baseline and publish
checksums for every image:

- boot.img
- vendor_boot.img
- dtbo.img
- recovery image or recovery ramdisk integrated into vendor_boot
- super.img (when produced by the selected AOSP target)
- SHA256SUMS

## Compatibility rules

1. Sync device, kernel, and vendor sources from the same Android 15 QPR1
   baseline.
2. Do not mix monthly vendor blobs with a different platform release.
3. Require the device bootloader to meet or exceed the selected factory image
   anti-rollback level before flashing.
4. Keep the first release userdebug for development; produce a separate
   user variant only after bring-up and recovery validation.

This document records the target only. It does not authorize flashing a device
or bypassing Android Verified Boot.
