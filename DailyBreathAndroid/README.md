# Daily Breath for Android

Native Android companion to `DailyBreathApple`, built with Java 17 and the Android view toolkit.

## Android 2.4 Play release

Version 2.4.0 uses version code 7. Build the signed Play App Bundle locally with `build-play-review.ps1`, which checks that the signing certificate matches the upload key registered with Google Play. The script prompts for the keystore passwords without storing them in the repository. Upload the resulting AAB in Play Console.

The Play Console privacy policy declaration and the in-app policy link must both use the public, app-specific page at `https://beyondimagination.co.technology/dailybreath/privacy.php`. Check that page without a signed-in browser session before sending a release for review.

## Android 2.3 update

- Sync the selected day, tradition, and language from the Daily Breath daily-content API, with a date-specific on-device cache and bundled offline fallback
- Prepare daily Bible, Tanakh, and Quran narration on demand and save the MP3 on device for offline replay
- Share a public reading link or export a branded scripture card using the selected scripture artwork theme
- Choose English or the original-language reading for Tanakh and Quran on Today; the choice also updates the home-screen widget
- Preserve complete passages in exported cards, letting the image grow for longer readings
- Keep a person’s chosen appearance when they switch traditions; add Seasonal, Bible Forest, Tanakh Navy & Gold, and Quran Emerald & Gold themes
- Render Hebrew and Arabic daily passages right-to-left and keep reading text selectable
- Add a resizable home-screen reading widget that follows the selected tradition, theme, and synced daily passage

## Existing Android features

- Offline Bible, Tanakh, and Quran readers and search, with faith-appropriate daily readings and fallbacks
- Today, Scripture, Chat, Academy, Breathe, and Journal navigation
- In-app Chris, Dovi, and Moe chat with illustrated guides, multilingual prompts, and Daily Breath-only/no-GPU request enforcement
- Daily Breath-specific Privacy Policy, Terms & Conditions, and Data & Account Controls links
- Peace Breath session with phase cues, pause/repeat, and persisted daily completion
- Shared `dailybreath://today`, `dailybreath://breathe`, `dailybreath://scripture`, `dailybreath://chat`, `dailybreath://academy`, and `dailybreath://journal` deep links
- Material-friendly forest visual language matching the web and iOS apps

Narration requires a server-side ElevenLabs API key and a configured voice for the requested locale in Daily Breath Premium Voices. A tap on Today prepares that passage’s MP3 once; the server shares its cached recording across clients, and Android keeps a private on-device copy for offline replay. The app does not include the provider key.
- Saved first-launch interface selection for English, French, and Spanish

## Build

Open this directory in Android Studio, install API 37 when prompted, and use **Build > Generate App Bundles or APKs**. The project uses the same Android Gradle Plugin 9.3.0 / Java 17 baseline as `BeyondTVAndroid`.

The next Android slices can add lesson state, encrypted journal storage, notifications, and additional widget controls once the base app is installed and verified on a device.

## Store releases

The dependency-free `amazon` flavor produces an APK for Amazon Appstore submission:

```powershell
.\gradlew.bat assembleAmazonRelease
```

For a signed distributable APK, use the manual `azure-pipelines-dailybreath-amazon-release.yml` pipeline. Create the `dailybreath-amazon-release` variable group with secret `AMAZON_KEYSTORE_PASSWORD` and `AMAZON_KEY_PASSWORD`, plus non-secret `AMAZON_KEY_ALIAS`; upload `DailyBreath_Amazon_Release.keystore` as an Azure Secure file. Give every submitted build a higher `androidVersionCode` than its predecessor. The pipeline publishes the signed `amazon/release` APK as `dailybreath-amazon-apk` without placing signing material in the repository or log output.

Google Play requires an Android App Bundle rather than an APK for a new app. Use the manual `azure-pipelines-dailybreath-play-release.yml` pipeline, which runs `bundlePlayRelease` and publishes `dailybreath-play-aab`. Create the `dailybreath-play-release` variable group with secret `PLAY_KEYSTORE_PASSWORD` and `PLAY_KEY_PASSWORD`, plus non-secret `PLAY_KEY_ALIAS`; upload `DailyBreath_Play_Upload.keystore` as an Azure Secure file. Keep the upload key safe: Play requires later updates to use the same package name and upload key.

The Codemagic `daily-breath-android-play` workflow builds the signed bundle and uploads it to the Google Play production track as a draft. Before starting it, create the `google_play` environment group in Codemagic with the secret `GOOGLE_PLAY_SERVICE_ACCOUNT_CREDENTIALS`, grant that service account access to package `technology.co.beyondimagination.dailybreath` in Play Console, and configure the `daily-breath-play-upload` signing key. The first Play upload must be made from Play Console; later workflow runs can publish automatically.
