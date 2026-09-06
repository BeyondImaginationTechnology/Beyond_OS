# BIT OS Cyber 1.0

BIT OS Cyber is a defensive Linux workstation for security-aware users,
education, incident review, and privacy-preserving local work. It is a separate
product from BIT OS Home and shares only the upstream-built platform foundation.

## Product baseline

- A non-root Cyber desktop session with no remote login enabled by default.
- A local evidence workspace at `~/Documents/Cyber/Evidence` for user-created
  notes and collected files.
- A stateful inbound firewall: loopback, established traffic, and DHCP replies
  are allowed; unsolicited inbound traffic is denied.
- UEFI-only images with explicit target selection in the installer. The
  installer never selects or erases a physical disk automatically.
- No inherited distribution branding in the target identity.

## Intentional limits

Cyber 1.0 is a defensive workstation, not an offensive toolkit. It does not
ship exploit frameworks, credential collection tools, persistence tooling, or
automated scanning against third-party systems. Any future diagnostic feature
must be local-first, visible to the user, and documented before inclusion.

## Release gates

Before publication, validate firewall behavior, local evidence persistence,
UEFI boot, selected-partition installation, whole-disk installation on a
disposable disk, and an installed-system reboot. Secure Boot, full-disk
encryption, signed updates, and a support policy remain planned release gates.
