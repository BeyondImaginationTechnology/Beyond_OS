# Beyond Kitchen 0.0.2

Beyond Kitchen 0.0.2 includes a responsive PHP web app and starter native
Android and iOS clients. Each has a date-based daily pick, recipe search and
filters, step-by-step instructions, adjustable servings, and favorites stored
on the device. Recipe content is maintained once in `data/recipes.json` and
bundled directly from that shared catalog into the native apps. Favorites are
not synced to a Beyond ID account.

The web page follows the PHP architecture in this repository and can run from
the same PHP-capable web server as Beyond OS. Its nine recipes include three
Haitian plates. It is also an installable PWA when served over HTTPS (or
localhost), with its recipe library, interface, and bundled photography cached
for offline visits. The lemon chickpea feature carousel uses five local 4:5
images and includes Gloria Liu's Unsplash photo credit.
The native clients use their platform UI and bundled recipes, and work without
network access.

## Native clients

- `BeyondKitchenAndroid/` is a native Java Android app. Build on Windows with
  `gradlew.bat assembleDebug` from that folder. It uses Java 17 and Android SDK
  37, in line with the repository's current Android project configuration.
- `BeyondKitchenApple/` is a native SwiftUI iOS app. Generate its Xcode project
  from that folder with `xcodegen generate`, then build the `BeyondKitchen`
  scheme in Xcode.

The Android and iOS app projects are separate, as in the rest of the
repository; there is no shared native UI framework. Store publishing,
Beyond ID sign-in, cloud recipe sync, and cross-device favorite sync are not
part of this starter release.

## Local check

From the repository root, run `php -l beyond-kitchen/index.php`,
`node --check beyond-kitchen/assets/js/app.js`, and
`node --test beyond-kitchen/tests/recipe-library.test.js`. Native build commands
are listed above and require the platform SDKs.
