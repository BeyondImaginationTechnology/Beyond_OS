# Academy 0.1 source-image validation

Validated on 2026-10-06 (Pacific time) from the GCP build VM `bit-os-core-test-a`.

| Check | Result |
| --- | --- |
| Build profile | `academy` |
| Kernel image | `bzImage`, 6.4 MiB |
| Root filesystem | `rootfs.ext2`, 512 MiB |
| Root filesystem label | `BEYOND_ACADEMY` |
| Academy executable | `/usr/bin/beyond-academy` exists and is executable in `rootfs.ext2` |
| OS identity | `Beyond Imagination OS Academy Edition`, `BIT OS Academy v0.1 (Development Preview)` |
| Build identifier | `academy-0.1-dev.1` |

## SHA-256

The generated manifest matches the independently calculated hashes:

```text
e7561172e8997f38d3e153f20415c6b2161bf77890e5dbed338b925d149ad2f7  bzImage
6bc759395430b55eceb5b0f49ab95a84dd068974072ea3bc0bfe32a833515ddc  rootfs.ext2
```

No boot test or installer-media build was performed for this source-image validation.
