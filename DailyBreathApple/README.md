# DailyBreath for iOS

Native SwiftUI app for DailyBreath 1.0.

## Included in this build

- Verse of the Day with speech playback
- Daily devotional card
- Bible library starter view
- Bible Academy module list
- Peace Breath practice
- Private in-memory reflection journal

## Build

Generate the Xcode project with `xcodegen generate`, then open `DailyBreath.xcodeproj`.

The first version is intentionally self-contained and works without a backend. Future builds can connect `DailyBreathStore` to the DailyBreath web API for live verses, devotionals, academy progress, accounts, and subscriptions.
