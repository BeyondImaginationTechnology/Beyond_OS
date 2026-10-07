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

## UEFI installer media validation

Validated on 2026-10-06 (Pacific time) from the same GCP build VM.

| Check | Result |
| --- | --- |
| ISO | `bitAcademyos.iso`, 86,843,392 bytes |
| USB installer image | `bit-os-academy-0.1-installer.img`, 2,182,107,136 bytes |
| Installer root label | `BEYOND_ACADEMY` |
| Flavour metadata | `academy` |
| UEFI menu | `Try BIT OS Academy v0.1` and `Install BIT OS Academy v0.1` entries present |
| OS identity | `Beyond Imagination OS Academy Edition`, build `academy-0.1-dev.1` |

The generated `SHA256SUMS` manifest matches the installer image, ISO, and
installer root filesystem:

```text
916672466db0bec4d252e80c187065c52be63ce265e2c1b469c47d1b5eed5c12  bit-os-academy-0.1-installer.img
25b0ccc372fd6c5a7c836aea108d8481f513d91dcdacde0f00584134bb33d332  bitAcademyos.iso
b059c9d5631be7f0222b73bd743aad12361ad060f97b24922e1afd564de58e01  rootfs.ext2
```

No installer boot test, publication, or deployment was performed.
