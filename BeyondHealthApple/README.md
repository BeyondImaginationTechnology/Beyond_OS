# Beyond Health iOS v0.0.2

Native SwiftUI daily wellbeing companion for iOS 17 and later.

The app has Today, Journal, Calendar, Practices, Insights, and Settings. Check-ins record mood, energy, stress, and sleep. The Calendar plans breakfast, lunch, and dinner from the shared Beyond Kitchen recipe catalog. Each morning has an available prep-time setting and a breakfast suggestion that fits it. The current week's recipes create an ingredient list. Journal notes, check-ins, and the plan are stored in the app's private Application Support directory with iOS file protection. Settings can delete all local entries.

The recipe catalog is bundled at build time from `../beyond-kitchen/data/recipes.json`. The app works offline and does not sync with the web app or Android. It does not ask for HealthKit or network access.

Open `BeyondHealthMobile.xcodeproj` in Xcode or regenerate it with XcodeGen:

```sh
xcodegen generate
```

Select the `BeyondHealthMobile` scheme and build for an iOS 17+ device or simulator. Bundle ID: `technology.co.beyondimagination.beyondhealthmobile`.
