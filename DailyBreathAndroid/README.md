# Daily Breath for Android

Native Android companion to `DailyBreathApple`, built with Java 17 and the Android view toolkit so the first release has no third-party runtime dependency.

## Included in this first slice

- Offline-first Verse of the Day from the shared bundled recovery library
- Today, Scripture, Academy, Breathe, and Journal navigation
- Peace Breath session with phase cues, pause/repeat, and persisted daily completion
- Shared `dailybreath://today`, `dailybreath://breathe`, `dailybreath://scripture`, `dailybreath://academy`, and `dailybreath://journal` deep links
- Material-friendly forest/cream/gold visual language matching the iOS app

## Build

Open this directory in Android Studio, install API 37 when prompted, and use **Build > Generate App Bundles or APKs**. The project uses the same Android Gradle Plugin 9.3.0 / Java 17 baseline as `BeyondTVAndroid`.

The next Android slices can add the full Bible parser, Academy lesson state, encrypted journal storage, notifications, audio narration, widgets, and Play Billing once the base app is installed and verified on a device.
