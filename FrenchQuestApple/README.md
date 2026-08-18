# French Quest for iOS

Native SwiftUI game build for French Quest 1.1.1.

## Launch Features

- Main menu with New Game, Load Game, and Settings
- Interactive world-tour map across Port-au-Prince, Haiti, Morocco, Montréal, and France
- Fifteen sequential travel missions with destination unlocking
- Choice-based challenges for translation, listening, and culture
- Local XP, hearts, streaks, and region unlocks
- Expanded 158-entry phrasebook
- Bundled Azure neural French audio that plays without a network connection
- On-device voice fallback for content without a bundled recording
- Looping French accordion game soundtrack with a persistent mute control
- Training room for typed recall
- Themeable game shell
- Beyond ID sign-in with secure Keychain session storage
- Per-account cloud save/load with automatic gameplay sync

## Build

Open `FrenchQuest.xcodeproj` in Xcode, or build from the command line with:

```sh
xcodebuild -project FrenchQuest.xcodeproj -scheme FrenchQuest -destination 'generic/platform=iOS' build
```
