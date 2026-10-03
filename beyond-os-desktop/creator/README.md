# BIT OS Creator v0.1

Creator 0.1 is a development profile built on the BIT OS Core foundation. Its
native Creator Hub makes personalization the first experience: choose creative
apps, select a workspace, set a background playlist, and create standard
project folders.

## Included in the 0.1 source milestone

- Four editable workspace identities: Golden Hour Studio, Ink & Canvas,
  Electric Atelier, and Blue Note Studio.
- A twelve-app chooser with separate workspace and automatic-start choices.
- Automatic-start choices launch installed applications on the next session;
  app cards mark unavailable integration targets as `PLANNED`.
- Beyond Music playlist selection and Beyond Wallpapers workspace selection.
- Persistent choices in `~/.config/beyond-creator/settings`.
- Projects, Assets, Recordings, Templates, Exports, and Fonts folders.
- Creator-specific system identity and startup routing.

The third-party app cards describe integration targets. Creator 0.1 does not
claim those applications are bundled until their individual Buildroot packages
are enabled and accepted. Beyond Music 0.1 saves the selected playlist and
visual playback state; streaming and local audio playback remain a later
milestone.

## Configure on Linux

```sh
cd ../core
BEYOND_FLAVOUR=creator BEYOND_BUILD_DIR=../creator/out bash build.sh configure
```

Use `build` or `installer` in place of `configure` for full image work. Creator
remains a development preview until its image and installer gates are recorded.

For a fast compile against an existing Core output before rebuilding an image:

```sh
sh tools/build-preview.sh /path/to/core/out/output ./creator-preview
```
