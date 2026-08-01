# Beyond Tattoo Apple

Native SwiftUI companion app for Beyond Tattoo on iOS and macOS.

## Includes

- Daily stencil drop with preview, save state, reward bits, and download links.
- Collection browser seeded from the current Beyond Tattoo storefront schedule.
- Healing tracker timeline for photo logs and care milestones.
- Studio directory surface with verified studio cards.
- Beyond ID beta/profile shell with role switching for collectors, artists, and studios.

## Build

Generate the Xcode project from the site root:

```sh
cd BeyondTattooApple
xcodegen generate
```

Then open `BeyondTattoo.xcodeproj` and run either `BeyondTattoo-iOS` or `BeyondTattoo-macOS`.
