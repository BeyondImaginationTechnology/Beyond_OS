# DailyBreath native 2.5: three-app plan

## Product decision

Ship three distinct native products on both iOS and Android:

| App | Existing or new listing | Sacred-text scope | Daily program and guide |
| --- | --- | --- | --- |
| Daily Breath Bible | Update the current DailyBreath listing and preserve its existing identifiers | Bible | Chris; Verse of the Day, Christian reflections, prayer, and recovery learning |
| Daily Breath Torah | New listing | Full Tanakh reader, with Torah featured prominently | Dovi; daily passage, Hebrew-first reading, Jewish learning and reflection |
| Daily Breath Quran | New listing | Quran | Moe; daily ayah, Arabic-first reading, Muslim learning and reflection |

The Torah name is the product name, while the library must accurately label the full Hebrew Bible as **Tanakh**. The five books of Torah should have a dedicated entry point within it.

## Identity and packaging

- Keep the existing iOS Bible bundle ID `technology.co.beyondimagination.thedailybreath` and Android Bible application ID `technology.co.beyondimagination.dailybreath` so their store records and update paths continue.
- Reserve distinct new identifiers for Torah and Quran on each platform. Proposed IDs (verify availability before registering): iOS `technology.co.beyondimagination.thedailybreath.torah` and `.quran`; Android `technology.co.beyondimagination.dailybreath.torah` and `.quran`.
- Build from shared native source, with a compile-time product identity for each app. Share networking, local storage, player, journal, accessibility, notifications, and core UI components. Build separate app targets/configurations on iOS and a `faith` product-flavor dimension on Android (alongside the existing `store` dimension).
- Give each app its own icon, display name, deep-link host or scheme, widget identity, app-group/storage identifiers where needed, notification copy, screenshots, description, privacy answers, and release track.

## What must be truly distinct

- Start each app directly in its own tradition. Remove the cross-tradition picker from the three store builds.
- Bundle only that app's sacred-text resources and defaults. Preserve native right-to-left layout for Hebrew and Arabic, including search, navigation, and accessibility.
- Provide an independently authored daily program, episode catalog, guide, academy path, and practices for each tradition. Shared tools such as breathing and journaling can remain shared, but the main journey cannot be a palette swap.
- Route the existing daily-content API by tradition and keep voice, artwork, terminology, citations, and notifications scoped to the selected product.
- Review text, translations, references, and imagery for each tradition before release.

## Migration sequence

1. Build and test Torah and Quran as new native apps while the existing combined app remains usable.
2. Add a handoff in the existing app for current Torah and Quran users. Preserve or export their journal, preferences, reading position, and reminders before the Bible-only update; do not silently discard them.
3. Release the two new apps, verify their store availability and deep links, then update the existing app to Bible-only.
4. Use separate TestFlight and Play internal-testing tracks for each product. Verify install, update, offline text, narration, right-to-left reading, widgets, notifications, account state, and migration on physical devices.

## Store-review risk

Separate listings are technically possible, but approval is not automatic. Apple Guideline 4.3(a) cautions against multiple bundle IDs for the same app, and Google Play's repetitive-content policy cautions against apps with highly similar functionality, content, and user experience. The existing iOS project already has a Guideline 4.3(a) response. The review submission should show the distinct daily programming and text experiences in working builds and screenshots, not just different names or colors.

## Current codebase anchors

- iOS SwiftUI project: `DailyBreathApple/`; version 2.4, one interfaith app target, widget and App Clip.
- Android Java project: `DailyBreathAndroid/`; version 2.4.0, one interfaith app module with `play` and `amazon` store flavors.
- Web/content backend: `dailybreath/`; already stores and serves Bible, Torah/Tanakh, and Quran daily content by tradition.

## Sources checked 2026-10-07

- Apple App Review Guidelines 4.3: https://developer.apple.com/app-store/review/guidelines/
- Google Play repetitive-content policy: https://support.google.com/googleplay/android-developer/answer/9899034
- Apple App Store Connect app records: https://developer.apple.com/help/app-store-connect/create-an-app-record/add-a-new-app/
- Google Play create and set up an app: https://support.google.com/googleplay/android-developer/answer/9859152
