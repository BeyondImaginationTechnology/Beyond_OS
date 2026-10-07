# BIT OS Sentinel v0.1

Sentinel is a quiet operations workspace for a single device first. Its native
Sentinel Console makes the device posture, local policy preferences and report
folders visible without claiming a hosted fleet-management service.

The shared Core builder selects Sentinel with:

```sh
BEYOND_FLAVOUR=sentinel BEYOND_BUILD_DIR=../sentinel/out bash ../core/build.sh build
```

The 0.1 preview defaults to no remote login and no fleet enrollment. Enrollment
is an explicit future choice; this release only stores the local preference.
