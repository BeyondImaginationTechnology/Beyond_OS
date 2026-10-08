# Gaming 0.1 source-image validation

Validated on 2026-10-07 from the GCP build VM `bit-os-core-test-a`.

| Check | Result |
| --- | --- |
| Build profile | `gaming` |
| Kernel image | `bzImage`, 6.4 MiB |
| Root filesystem | `rootfs.ext2`, 512 MiB |
| Root filesystem label | `BEYOND_GAMING` |
| Gaming executable | `/usr/bin/beyond-gaming` exists and is executable in the target filesystem |
| OS identity | `Beyond Imagination OS Gaming Edition`, `BIT OS Gaming v0.1 (Development Preview)` |
| Build identifier | `gaming-0.1-dev.1` |

The generated `SHA256SUMS` manifest:

```text
b5d74817b5e88fa7b9465304db5a64fe25a70e6cf0edbbeb8ac633b467afb8aa  bzImage
1c3e5b26111b1ac46f1064c4a409fb5413d024570853e57155ca847573b56333  rootfs.ext2
```

The source-image build completed successfully. Installer media preparation is
the next release step.
