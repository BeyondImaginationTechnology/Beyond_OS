# DailyBreath for iOS

Native SwiftUI app for DailyBreath 1.2 (build 1).

## Included in this build

- Automatically synced Verse of the Day, devotional, and weekly recovery challenge
- Bundled offline recovery verse, devotional, and challenge libraries
- Recovery Newsletter digest built from the current synced content
- Full local World English Bible with background loading and search
- Bible Academy starter lessons with narration and saved progress
- Guided breathing practices and optional daily reminders
- Private, file-protected reflection journal stored on device
- Persistent weekly challenge completion tracking
- Home Screen and Lock Screen Verse of the Day widget
- Settings and About hub for reminders, themes, narration, privacy, support, and version details
- Notification and widget deep links to Today, Breathe, and Journal
- Persistent Bible highlights, favorite collections, private notes, and adjustable full-chapter narration
- A gentle 45-day history for daily content, breathing, and reflection
- Optional encrypted iCloud sync backed by iCloud Keychain
- Recovery support and crisis-resource page with a clear non-medical disclaimer
- App Clip with the current bundled verse and native full-app install overlay

## Build

Generate the Xcode project with `xcodegen generate`, then open `TheDailyBreath.xcodeproj`.

The app works offline with bundled content and refreshes the verse, devotional, and weekly challenge from the DailyBreath web API when available. Private user data remains protected on device unless the user explicitly enables encrypted iCloud sync. No Daily Breath account or subscription is required.

## Release checks

- Enable the App Group and iCloud key-value-store capabilities for all required identifiers in the Apple Developer portal.
- Archive the parent app, widget, and embedded App Clip on macOS using automatic signing.
- Confirm the App Clip AASA file is deployed at `/.well-known/apple-app-site-association` with `application/json` content type and no redirect.
- Generate an Xcode privacy report from the archive and keep App Store Connect privacy answers aligned with `PrivacyInfo.xcprivacy`.
- Exercise online, offline, stale-response, malformed-response, and timeout paths before submission.
