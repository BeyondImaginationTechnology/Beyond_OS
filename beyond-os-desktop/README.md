# Beyond OS Desktop 0.1 — Creator Edition

Beyond OS Desktop is a Linux-based creator workstation, not a new kernel. The 0.1 target is an Ubuntu 24.04 LTS remaster with the Linux kernel supplied by Ubuntu, GNOME as the stable desktop base, and a Beyond OS session layer.

## Product boundaries

- `Beyond OS Desktop`: launcher, visual system, Beyond ID sign-in, Studio shortcuts, and developer tooling.
- Ubuntu / Linux: kernel, drivers, package management, security maintenance, and hardware enablement.
- Beyond Studio: web production workspace launched from the desktop.

## First ISO milestone

1. Build an Ubuntu 24.04 LTS image with Cubic or `live-build` in CI.
2. Copy `overlay/` into the image filesystem.
3. Install the Beyond OS shell package and browser policy.
4. Test a signed image in a VM before testing hardware.

The 0.1 edition deliberately targets virtual machines and developer hardware first. Secure Boot signing, OEM hardware enablement, an updater channel, and a custom desktop shell are later release gates.
