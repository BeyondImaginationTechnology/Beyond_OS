# Daily Breath for Android

Native Android companion to `DailyBreathApple`, built with Java 17 and the Android view toolkit so the first release has no third-party runtime dependency.

## Included in this first slice

- Offline Bible, Tanakh, and Quran readers and search, with faith-appropriate daily readings and fallbacks
- Today, Scripture, Academy, Breathe, and Journal navigation
- Peace Breath session with phase cues, pause/repeat, and persisted daily completion
- Shared `dailybreath://today`, `dailybreath://breathe`, `dailybreath://scripture`, `dailybreath://academy`, and `dailybreath://journal` deep links
- Material-friendly dawn/lilac visual language matching the web and iOS apps

## Build

Open this directory in Android Studio, install API 37 when prompted, and use **Build > Generate App Bundles or APKs**. The project uses the same Android Gradle Plugin 9.3.0 / Java 17 baseline as `BeyondTVAndroid`.

The next Android slices can add lesson state, encrypted journal storage, notifications, widgets, and Play Billing once the base app is installed and verified on a device.

## Store releases

The dependency-free `amazon` flavor produces an APK for Amazon Appstore submission:

```powershell
.\gradlew.bat assembleAmazonRelease
```

For a signed distributable APK, use the manual `azure-pipelines-dailybreath-amazon-release.yml` pipeline. Create the `dailybreath-amazon-release` variable group with secret `AMAZON_KEYSTORE_PASSWORD` and `AMAZON_KEY_PASSWORD`, plus non-secret `AMAZON_KEY_ALIAS`; upload `DailyBreath_Amazon_Release.keystore` as an Azure Secure file. Give every submitted build a higher `androidVersionCode` than its predecessor. The pipeline publishes the signed `amazon/release` APK as `dailybreath-amazon-apk` without placing signing material in the repository or log output.

Google Play requires an Android App Bundle rather than an APK for a new app. Use the manual `azure-pipelines-dailybreath-play-release.yml` pipeline, which runs `bundlePlayRelease` and publishes `dailybreath-play-aab`. Create the `dailybreath-play-release` variable group with secret `PLAY_KEYSTORE_PASSWORD` and `PLAY_KEY_PASSWORD`, plus non-secret `PLAY_KEY_ALIAS`; upload `DailyBreath_Play_Upload.keystore` as an Azure Secure file. Keep the upload key safe: Play requires later updates to use the same package name and upload key.
