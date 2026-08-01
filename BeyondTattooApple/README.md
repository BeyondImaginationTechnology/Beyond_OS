# Beyond Tattoo Apple

Native SwiftUI companion app for Beyond Tattoo on iOS and macOS.

## Includes

- Daily stencil drop with preview, save state, reward bits, and download links.
- Collection browser seeded with all 55 storefront stencil drops, search, availability, and asset badges.
- Healing tracker timeline for photo logs and care milestones.
- Location-aware studio directory showing the nearest 10 studios when location access is enabled.
- Beyond ID beta/profile shell with role switching for collectors, artists, and studios.

## Build

Generate the Xcode project from the site root:

```sh
cd BeyondTattooApple
xcodegen generate
```

Then open `BeyondTattoo.xcodeproj` and run either `BeyondTattoo-iOS` or `BeyondTattoo-macOS`.
