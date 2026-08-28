# Beyond TV for Android

Native Android web-app shell for the canonical Beyond TV experience.

## Release

- Version name: `1.1.0`
- Version code: `110`
- Application ID: `technology.co.beyondimagination.beyondtv`
- Minimum Android version: Android 8.0 (API 26)
- Target/compile SDK: API 37
- Java: 17
- Android Gradle Plugin: 9.3.0

## Build

Open this directory in Android Studio Quail 3 or newer, install API 37 when prompted, and use **Build > Generate App Bundles or APKs > Generate APKs**. A release APK must be signed with the owner's private signing key; no signing secret is committed to this repository.

The app keeps Beyond TV and Beyond ID pages inside its secure WebView, opens unrelated domains in the user's browser, rejects cleartext traffic, retains web sessions with cookies, supports video playback, and provides an offline retry state.
