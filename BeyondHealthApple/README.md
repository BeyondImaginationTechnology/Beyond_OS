# Beyond Health iOS v0.0.1

Native SwiftUI daily wellbeing companion for iOS 17 and later.

The app has five tabs: Today, Journal, Practices, Insights, and Settings. Check-ins record mood, energy, stress, and sleep. A selected mood suggests one short practice. Journal notes and check-ins are stored in the app's private Application Support directory with iOS file protection. Settings can delete all local entries.

This first native release is offline and does not sync with the web app or Android. It does not ask for HealthKit or network access.

Open `BeyondHealthMobile.xcodeproj` in Xcode or regenerate it with XcodeGen:

```sh
xcodegen generate
```

Select the `BeyondHealthMobile` scheme and build for an iOS 17+ device or simulator. Bundle ID: `technology.co.beyondimagination.beyondhealthmobile`.
