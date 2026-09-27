# Beyond Tattoo Apple

Native SwiftUI companion app for Beyond Tattoo on iOS. Desktop users install the responsive web app as a PWA.

## Includes

- Version 1.2 asset-backed daily stencil with preview, save state, reward bits, and real download links.
- Collection browser populated from the shared library manifest; only drops with actual preview and print-ready files appear.
- Healing tracker timeline for photo logs and care milestones.
- Location-aware Canadian studio directory with nine Ottawa listings and national coverage, showing the nearest 10 in kilometres.
- Beyond ID beta/profile shell with role switching for collectors, artists, and studios.
- In-app stencil editor session and Needle Bot tattoo companion, keeping Jaguar/editor requests inside the app.

## Build

Generate the Xcode project from the site root:

```sh
cd BeyondTattooApple
xcodegen generate
```

Then open `BeyondTattoo.xcodeproj` and run `BeyondTattoo-iOS`.
