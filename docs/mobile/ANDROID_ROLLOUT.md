# Android rollout

Android projects currently in the workspace:

| Product | Android status | Source of truth |
| --- | --- | --- |
| Daily Breath | Native v1 created | `DailyBreathApple` |
| Beyond TV | Existing Android project | `BeyondTVAndroid` |
| Beyond Games | Native v1 created with five playable catalog games | `beyond-games/data/games.json` |
| Beyond Baby Names | Next | `BeyondBabyNamesApple` |
| Beyond French | Next | `BeyondFrenchApple` |
| Beyond Health | Next | `BeyondHealthApple` |
| Beyond Math | Next | `BeyondMathApple` |
| Beyond Music | Next | `BeyondMusicApple` |
| Beyond Space | Next | `BeyondSpaceApple` |
| Beyond Tattoo | Next | `BeyondTattooApple` |
| Beyond Pay Wallet | Next | `BeyondWalletApple` |
| French Quest | Delivered as a playable mode in Beyond Games v1; standalone port follows | `FrenchQuestApple` |

## Release order

1. Daily Breath: add the full scripture, academy, journal-security, audio, notification, and widget layers.
2. Beyond Baby Names, Beyond French, and Beyond Health: ship the cleanest standalone v1s from their mature iOS equivalents.
3. Beyond Space, Tattoo, Math, Music, and Pay Wallet: port their specific offline data, display, and account/security requirements.
4. French Quest standalone: build from the full iOS game resources after the shared games foundation is device-tested.

Every Android project uses an independent application ID and needs a distinct Play Console app record, release signing key, privacy declaration, content rating, and store listing before publishing.
