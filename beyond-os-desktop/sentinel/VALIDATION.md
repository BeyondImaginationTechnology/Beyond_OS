# Sentinel 0.1 source-image validation

Validated on 2026-10-07 from the GCP build VM `bit-os-core-test-a`.

| Check | Result |
| --- | --- |
| Build profile | `sentinel` |
| Kernel image | `bzImage`, 6.4 MiB |
| Root filesystem | `rootfs.ext2`, 512 MiB; filesystem state clean |
| Root filesystem label | `BEYOND_SENTINEL` |
| Flavour metadata | `sentinel` |
| Sentinel executable | `/usr/bin/beyond-sentinel` exists and is executable in the build target; 64-bit x86-64 ELF |
| OS identity | `Beyond Imagination OS Sentinel Edition`, `BIT OS Sentinel v0.1 (Development Preview)` |
| Build identifier | `sentinel-0.1-dev.1` |

The generated `SHA256SUMS` manifest matches independently calculated hashes:

```text
ca8adf1f5785071ed45922adf4370b45290020087f734d168dfec8288ca8f88c  bzImage
82fad0a02f8f877651c1f361a40547691a5cbfc270930a76d43f51455c6921dd  rootfs.ext2
```

The source-image build completed successfully. No boot test, installer-media
build, publication or deployment was performed in this validation.
