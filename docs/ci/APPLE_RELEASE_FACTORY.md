# Apple TestFlight release factory

Azure can host releases for every iOS app in this repository. It is one shared
system, not one Azure account per app. Keep release pipelines manual-only: a
release is selected deliberately, signed in an ephemeral macOS agent, uploaded
to TestFlight, then Apple processes it separately.

## Reusable credentials

The Apple team can reuse these two assets across its apps:

1. One Apple Distribution certificate (`.p12`) and its secret export password.
2. One App Store Connect API key (`.p8`, Key ID, Issuer ID), created by a user
   who has access to every app that will be released.

Do not reuse provisioning profiles across different bundle IDs. Every app needs
one App Store profile, and every extension or App Clip needs another profile.
All credentials belong in Azure Secure Files/secret variables, never Git.

## iOS release inventory

| App | Project | iOS scheme | Primary bundle ID | Extra distribution profiles |
| --- | --- | --- | --- | --- |
| Beyond Baby Names | `BeyondBabyNamesApple/BeyondBabyNames.xcodeproj` | `BeyondBabyNames` | `technology.co.beyondimagination.beyondbabynames` | — |
| Beyond French | `BeyondFrenchApple/BeyondFrench.xcodeproj` | `BeyondFrench` | `technology.co.beyondimagination.beyondfrench` | — |
| Beyond Health | `BeyondHealthApple/BeyondHealthMobile.xcodeproj` | `BeyondHealthMobile` | `technology.co.beyondimagination.beyondhealthmobile` | — |
| Beyond Math | `BeyondMathApple/BeyondMath.xcodeproj` | `BeyondMath` | `technology.beyondimagination.beyondmath` | — |
| Beyond Music | `BeyondMusicApple/BeyondMusic.xcodeproj` | `BeyondMusic` | `technology.co.beyondimagination.beyondmusic` | — |
| Beyond Space | `BeyondSpaceApple/BeyondSpace.xcodeproj` | `BeyondSpace` | `technology.co.beyondimagination.beyondspace` | — |
| Beyond Tattoo | `BeyondTattooApple/BeyondTattoo.xcodeproj` | `BeyondTattoo-iOS` | `technology.co.beyondimagination.beyondtattoo` | — |
| Beyond TV | `BeyondTVApple/BeyondTV.xcodeproj` | `BeyondTV-iOS` | `technology.co.beyondimagination.beyondtv` | — |
| Beyond Pay | `BeyondWalletApple/Beyond Pay Mobile.xcodeproj` | `BeyondPayMobile-iOS` | `technology.co.beyondimagination.beyondpaymobile` | — |
| The Daily Breath | `DailyBreathApple/TheDailyBreath.xcodeproj` | `The Daily Breath` | `technology.co.beyondimagination.thedailybreath` | widget and App Clip |
| French Quest | `FrenchQuestApple/FrenchQuest.xcodeproj` | `FrenchQuest` | `technology.co.beyondimagination.frenchquest` | — |

`BeyondTattoo-macOS` and `BeyondTV-tvOS` are separate platform releases and are
not included in this iOS/TestFlight inventory.

## Rollout order

1. Complete DailyBreath first. It is the best proof case because it has the
   most difficult signing layout: app, widget, and App Clip.
2. Promote the proven Azure signing pattern to a shared template.
3. Create one small manual release wrapper per remaining iOS app. Beyond Baby
   Names now has `azure-pipelines-beyondbabynames-release.yml`; each wrapper
   specifies only its project, scheme, bundle ID, and Secure File profile.
4. Confirm that an App Store Connect app record exists before activating each
   wrapper. A successful Azure archive cannot upload to a missing app record.

Once DailyBreath has one successful TestFlight upload, adding a standard
single-target iOS app is a short credential-and-configuration task rather than
a new CI design.
