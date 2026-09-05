# Beyond OS Home Edition 1.0

Beyond OS Home is an independent Linux product assembled from upstream source.
The `home/` Buildroot external tree replaces the previous desktop overlay.
Buildroot is build tooling, not the installed distribution. The target identifies
itself as `beyond-os`, with no inherited distribution name or `ID_LIKE`.

**Status: development source; full image compilation and VM boot validation are
pending. This is not a released or hardware-qualified 1.0 image.**

The first milestone is an x86-64 QEMU system: Linux, musl, BusyBox init, X.Org,
Openbox, and a native SDL2 Home shell. Beyond supplies the system identity,
framebuffer boot screen, desktop, build configuration, and release requirements.

The Home shell includes a directory viewer, a persistent text note, a terminal
launcher, and system information. It is a minimal desktop foundation. A modern
browser, streaming, audio controls, account setup, graphical installation,
signed updates, and hardware support remain release work.

See [the build guide](home/README.md) and [release gates](home/RELEASE.md).
