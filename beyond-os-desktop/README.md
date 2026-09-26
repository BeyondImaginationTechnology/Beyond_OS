# BIT OS Home v0.1

Beyond OS Home is an independent Linux product assembled from upstream source.
The `home/` Buildroot external tree replaces the previous desktop overlay.
Buildroot is build tooling, not the installed distribution. The target identifies
itself as `beyond-os`, with no inherited distribution name or `ID_LIKE`.

**Status: development source; Home v0.1 image compilation and VM boot validation
are pending.**

The first milestone is an x86-64 QEMU system: Linux, musl, BusyBox init, X.Org,
Openbox, and a native SDL2 Home shell. Beyond supplies the system identity,
framebuffer boot screen, desktop, build configuration, and release requirements.

The Home shell includes a desktop dashboard, directory viewer, persistent note,
WebKitGTK browser launcher, local FFplay media launcher, terminal and system
information. Browser/media operation, account setup, graphical installation,
signed updates, and hardware support remain release work.

See [the build guide](home/README.md) and [release gates](home/RELEASE.md).
