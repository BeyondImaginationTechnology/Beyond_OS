# Beyond Imagination OS flavour plan

The seven official 1.0 flavours are defined in `catalog.json`:

| Flavour | Current state | Next build milestone |
| --- | --- | --- |
| Home | Buildable | Complete QEMU and installer acceptance |
| Core | Buildable | Complete QEMU and installer acceptance |
| Creator | Profile defined | Add creator package profile and native shell identity |
| Academy | Profile defined | Add managed-learning package profile and policy defaults |
| Cyber | Buildable | Complete defensive-workstation acceptance |
| Sentinel | Profile defined | Add fleet/policy profile and management boundary |
| Gaming | Profile defined | Add graphics/input/performance profile |

All seven tracks can now be driven through `../build-all.sh`, which gives each
flavour its own Buildroot output directory. The four Core-based profiles share
the Core platform boundary and carry their flavour identity and metadata into
the image. Product-specific package overlays and release gates remain separate
work for each profile; a successful build is not by itself a stable release.

Creator exposes Adobe Creative Cloud through its web experience. Adobe's
current Creative Cloud desktop requirements list Windows and macOS, not Linux;
the Creator profile therefore does not bundle or claim a native Linux Adobe
desktop installer. See Adobe's [desktop requirements](https://helpx.adobe.com/creative-cloud/apps/get-started/desktop-technical-requirements.html)
and [web-app guidance](https://helpx.adobe.com/download-install/apps/download-install-apps/creative-cloud-apps/download-creative-cloud-apps.html).

The Core-based profiles use the shared Core builder on a Linux build host:

```sh
BEYOND_FLAVOUR=creator bash ../core/build.sh configure
BEYOND_FLAVOUR=academy bash ../core/build.sh configure
BEYOND_FLAVOUR=sentinel bash ../core/build.sh configure
BEYOND_FLAVOUR=gaming bash ../core/build.sh configure
```

The builder also accepts `build`, `installer`, and `legal-info` after selecting
the profile. Home and Cyber continue to use their dedicated build trees until
their profile-specific package layers are folded into the shared builder.

## Google Cloud browser desktop

Core's cloud deployment is intended to expose its Openbox session through
noVNC over an IAP-authenticated HTTPS path. The guest desktop and VNC service
must remain private; the deployment must not publish TCP 5900 or 6080 to the
internet. This keeps the library-computer workflow browser-only while
retaining Google-account access control.

The cloud adapter is a deployment layer around the Core image. It is separate
from the seven flavour identities so the same browser access pattern can be
used for Core-based Creator, Academy, Sentinel, and Gaming test images.
