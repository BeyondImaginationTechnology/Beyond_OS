# Beyond Tattoo Apple

Native SwiftUI companion app for Beyond Tattoo on iOS and macOS.

## Includes

- Version 1.2 asset-backed daily stencil with preview, save state, reward bits, and real download links.
- Collection browser populated from the shared library manifest; only drops with actual preview and print-ready files appear.
- Healing tracker timeline for photo logs and care milestones.
- Location-aware Canadian studio directory with nine Ottawa listings and national coverage, showing the nearest 10 in kilometres.
- Beyond ID beta/profile shell with role switching for collectors, artists, and studios.

## Build

Generate the Xcode project from the site root:

```sh
cd BeyondTattooApple
xcodegen generate
```

Then open `BeyondTattoo.xcodeproj` and run either `BeyondTattoo-iOS` or `BeyondTattoo-macOS`.
