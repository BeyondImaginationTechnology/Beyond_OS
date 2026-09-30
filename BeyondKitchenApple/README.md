# Beyond Kitchen for iOS

Native SwiftUI companion for Beyond Kitchen 0.0.1. The XcodeGen project
includes the shared `beyond-kitchen/data/recipes.json` catalog and recipe photos
as bundle resources; favorites are stored locally with `UserDefaults`. The
daily pick has a five-card recipe carousel that works offline.
The "What's for dinner?" guide calls the hosted Beyond Kitchen endpoint; its
API key stays on the PHP server. If that service is unavailable, the app offers
a bundled recipe.

Generate `BeyondKitchen.xcodeproj` with `xcodegen generate`, then build or run
the `BeyondKitchen` scheme in Xcode. This project requires iOS 17 or later.
