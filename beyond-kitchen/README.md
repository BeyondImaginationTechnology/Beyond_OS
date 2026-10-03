# Beyond Kitchen 0.0.2

Beyond Kitchen 0.0.2 includes a responsive PHP web app and starter native
Android and iOS clients. Each has a date-based daily pick, recipe search and
filters, step-by-step instructions, adjustable servings, and favorites stored
on the device. Recipe content is maintained once in `data/recipes.json` and
bundled directly from that shared catalog into the native apps. Favorites are
not synced to a Beyond ID account.

The web page follows the PHP architecture in this repository and can run from
the same PHP-capable web server as Beyond OS. Its twelve recipes include three
Haitian plates and four breakfast options with prep times from 5 to 25 minutes.
It is also an installable PWA when served over HTTPS (or
localhost), with its recipe library, interface, and bundled photography cached
for offline visits. The daily carousel follows the same featured recipe and
shows five slides covering the dish, ingredients, cooking steps, and finished
plate. The page uses rendered 1080 × 1350 JPEGs when they are available and
falls back to readable recipe slides while offline or before the daily render.
The native clients bundle the recipe catalog and photos, so their carousels
work without network access.

## Daily Instagram carousel draft

The CLI renderer needs PHP GD with JPEG and FreeType support and a readable
TrueType font. It checks common DejaVu and Liberation font paths on Linux;
set `BEYOND_KITCHEN_FONT_FILE` if needed. The calendar defaults to
`America/Vancouver`; `BEYOND_KITCHEN_TIMEZONE` can override it. Add this cron
entry in the hosting control panel after deploying:

```cron
15 6 * * * cd /path/to/www && /usr/bin/php81 server/cron/daily-kitchen-carousel.php >> /path/to/private/logs/daily-kitchen-carousel.log 2>&1
```

Run `php server/cron/daily-kitchen-carousel.php` once to create today's draft.
An optional `YYYY-MM-DD` argument renders a specific date. The output is
`assets/images/daily/YYYY-MM-DD/slide-01.jpg` through `slide-05.jpg`, a dated
`manifest.json`, and `assets/images/daily/latest.json`. This directory is
ignored by Git so daily renders do not block deployments. The manifest includes
the slide copy, image paths, and a caption draft. Publishing to Instagram is
manual in this release; the cron prepares the draft.

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
