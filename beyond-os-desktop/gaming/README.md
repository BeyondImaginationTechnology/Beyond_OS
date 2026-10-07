# BIT OS Gaming v0.1

Gaming 0.1 is a development preview built on the Core profile. The native
Gaming Hub includes a small playable arcade game, controller detection, and
saved display and input preferences. Games, Saves, and Screenshots folders are
created in the user's Home folder.

The profile builds with:

```sh
BEYOND_FLAVOUR=gaming BEYOND_BUILD_DIR=../gaming/out bash ../core/build.sh build
```

The package list in `flavours/profiles.json` describes future integration
targets. Steam, Lutris, Gamescope, PipeWire, Vulkan drivers and xboxdrv are not
bundled in the 0.1 source image. Performance mode is an interface preference;
it does not alter CPU governors or GPU drivers in this preview.
