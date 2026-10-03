# Creator 0.1 validation

Status: development image build completed on 2026-10-03. This records the
source image build and filesystem checks; no VM boot, installer build, or
hardware installation was performed in this validation pass.

## Build environment

- Host: GCP `bit-os-core-test-a`, `northamerica-northeast2-a`.
- Isolated source checkout: `/home/goldenghostog/Beyond_Creator_0_1_build`.
- Buildroot output: `beyond-os-desktop/creator/out/output`.
- Build selection: `BEYOND_FLAVOUR=creator`, `BR2_JLEVEL=2`.
- Build script reached its successful artifact summary and generated `bzImage`
  and `rootfs.ext2` on 2026-10-03. The outer background wrapper then failed
  while writing its exit-status file, so no wrapper status code was recorded.
- The final build summary printed `BIT OS creator v0.2`, because the isolated
  checkout had the older generic summary string. The generated filesystem
  itself contains the Creator 0.1 identity below; the summary text did not
  change the image contents.

## Verified artifacts

| Artifact | Size | SHA-256 |
| --- | ---: | --- |
| `bzImage` | 6.4 MiB | `f0e3e2e65338f443a3eebc3ba2dd1e6eee4be0f4fb16372dd7d689946bf9f193` |
| `rootfs.ext2` | 512 MiB | `327e055482b4c2a960f47b6e8679166196a0bf4b44b476feb18eb11a5abc99de` |

`sha256sum -c SHA256SUMS` passed for both artifacts.

## Filesystem checks

- `/usr/share/beyond-imagination-os/flavour` contains `creator`.
- `/usr/bin/beyond-creator` exists in the root filesystem as an executable
  x86-64 musl ELF, 30,488 bytes, mode `0755`.
- `/etc/os-release` identifies `BIT OS Creator v0.1 (Development Preview)`;
  `VERSION_ID=0.1` and `BUILD_ID=creator-0.1-dev.1`.
- Creator Hub compiled with `-Wall -Wextra -Werror` against the Core Buildroot
  toolchain before the full image build.

## Remaining release checks

- Build installer media and verify its checksum manifest.
- Boot the Creator image under UEFI and inspect the desktop.
- Verify workspace, app selection, automatic startup, wallpaper, playlist,
  and folder behavior in the running image.
- Validate display scaling and supported hardware before calling Creator a
  release candidate.
