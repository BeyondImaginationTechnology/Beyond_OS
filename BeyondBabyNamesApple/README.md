# Beyond Baby Names 1.0 for iOS

A native SwiftUI baby-name discovery app based on the Beyond Baby Names brand.

## Included

- Search and filter by name, meaning, style, origin, and vibe.
- Private on-device favorites and preference-based smart recommendations.
- Swipe flow with Pass, Maybe, and Love decisions.
- Couple Mode with a shareable invite code, demo partner picks, and match reveals.
- Twin-name pair ideas generated from the user’s shortlist.
- iPhone, iPad, and Mac Catalyst targets with an App Store-ready 1024px icon.

## Build

On macOS, open the checked-in project directly:

```sh
open BeyondBabyNames.xcodeproj
```

Or regenerate it from the checked-in XcodeGen specification:

```sh
cd BeyondBabyNamesApple
xcodegen generate
open BeyondBabyNames.xcodeproj
```

The bundle identifier is `technology.co.beyondimagination.beyondbabynames`, marketing version is `1.0.0`, and signing is configured for the repository’s existing Apple team.
