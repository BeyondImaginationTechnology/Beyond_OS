# Beyond TV for Android

Native Android web-app shell for the canonical Beyond TV experience.

## Release

- Public version: `1.0`
- Version code: `2001`
- Application ID: `technology.co.beyondimagination.beyondtv`
- Minimum Android version: Android 8.0 (API 26)
- Target/compile SDK: API 37
- Java: 17
- Android Gradle Plugin: 9.3.0

## Build

Open this directory in Android Studio Quail 3 or newer, install API 37 when prompted, and use **Build > Generate App Bundles or APKs > Generate APKs**. A release APK must be signed with the owner's private signing key; no signing secret is committed to this repository.

The app keeps Beyond TV and Beyond ID pages inside its secure WebView, opens unrelated domains in the user's browser, rejects cleartext traffic, retains web sessions with cookies, supports video playback, and provides an offline retry state.

## Between-program ads

The Beyond TV web player starts a five minute break between shows and movies.
This Android shell uses Unity Ads Game ID `6197579` and the existing
`Interstitial_Android` ad unit for one native interstitial at the start of a
break. An origin-restricted WebView message bridge accepts requests only from
the main Beyond TV page. Debug builds request Unity test ads; release builds
request live inventory. A failed or unavailable native ad falls back to the
web player's configured VAST tag, then to the Bit Runner mini game.

Before release, confirm the app's Google Play Store ID, payout profile,
audience designation, and ad testing in the Unity dashboard. The server's
`BEYOND_TV_VAST_TAG_URL` setting controls the independent web ad fallback.
