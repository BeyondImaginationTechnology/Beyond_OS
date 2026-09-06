# Build BIT OS Cyber

BIT OS Cyber 1.0 is an independent defensive Linux workstation assembled from
upstream source with Buildroot as the build tool. It is not a flavor of another
distribution and identifies itself only as BIT OS Cyber.

The current foundation shares the proven UEFI platform work with BIT OS Home,
but has its own Buildroot external tree, product identity, artifact names, and
defensive defaults. See [CYBER.md](CYBER.md) for scope and release gates.

## Current build targets

Run on a normal-user x86-64 Linux host with the Buildroot prerequisite tools:

```sh
bash build.sh configure
bash build.sh build
bash build.sh installer
```

The regular build produces a QEMU-oriented system image. The installer target
produces `bitCyberos.iso` for a UEFI live/installer session and
`bit-os-cyber-1.0-installer.img` for a GPT USB installer image. Both remain
development candidates until all Cyber release gates pass.

## Defensive baseline

The target starts a stateful inbound firewall after networking: loopback,
established traffic, and DHCP client replies are allowed; unsolicited inbound
and forwarded traffic are denied. The desktop runs as the unprivileged `home`
account, and remote login is not enabled by default.
