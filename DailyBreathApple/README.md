# DailyBreath for iOS

Native SwiftUI app for DailyBreath 1.5 (build 2).

## Included in this build

- Automatically synced Verse of the Day, devotional, and weekly recovery challenge
- Bundled offline recovery verse, devotional, and challenge libraries
- Recovery Newsletter digest built from the current synced content
- Full local World English Bible with background loading and search
- Offline multilingual scripture editions: English/French/Spanish Bible; Hebrew-default Torah/Tanakh with English/French translations; Arabic-default Quran with an English meaning
- Tradition-native book names (including Bereshit, Shemot, and Tehillim) and right-to-left Hebrew/Arabic reading
- Full Quran library with all 114 surahs and 6,236 ayahs
- Matched Bible, Torah, and Quran Verse of the Day themes
- Exactly two Christian, Jewish, and Muslim Academy journeys—Joining the Faith and Recovery—with saved progress and a Beyond Imagination completion certificate
- Chris, Dovi, and Moe illustrated Academy guides
- Olive-era logo plus Torah Light and Quran Moon themes
- Guided breathing practices and optional daily reminders
- Private, file-protected reflection journal stored on device
- Persistent weekly challenge completion tracking
- Home Screen and Lock Screen Verse of the Day widget
- Settings and About hub for reminders, themes, privacy, support, and version details
- Notification and widget deep links to Today, Breathe, and Journal
- Persistent Bible highlights, favorite collections, and private notes
- A gentle 45-day history for daily content, breathing, and reflection
- Optional encrypted iCloud sync backed by iCloud Keychain
- Recovery support and crisis-resource page with a clear non-medical disclaimer
- App Clip with the current bundled verse and native full-app install overlay

## Build

Generate the Xcode project with `xcodegen generate`, then open `TheDailyBreath.xcodeproj`.

The app works offline with bundled content and refreshes the verse, devotional, and weekly challenge from the DailyBreath web API when available. Private user data remains protected on device unless the user explicitly enables encrypted iCloud sync. No Daily Breath account or subscription is required.

## Release checks

- In Apple Developer Certificates, Identifiers & Profiles, enable Associated Domains, App Groups, and iCloud key-value storage for `technology.co.beyondimagination.thedailybreath`; enable App Groups for `technology.co.beyondimagination.thedailybreath.widget`; and enable Associated Domains/App Clip for `technology.co.beyondimagination.thedailybreath.Clip`.
- Attach the app and widget identifiers to `group.technology.co.beyondimagination.thedailybreath`, then allow Xcode to regenerate all three provisioning profiles.
- Keep **Automatically manage signing** enabled for the parent app, widget, and App Clip. Do not pass a global `PROVISIONING_PROFILE_SPECIFIER` to `xcodebuild`; each bundled target requires its own profile.
- Archive the parent app on macOS. Xcode will select distribution identities and embed the separately signed widget and App Clip.
- Confirm the App Clip AASA file is deployed at `/.well-known/apple-app-site-association` with `application/json` content type and no redirect.
- Generate an Xcode privacy report from the archive and keep App Store Connect privacy answers aligned with `PrivacyInfo.xcprivacy`.
- Exercise online, offline, stale-response, malformed-response, and timeout paths before submission.
