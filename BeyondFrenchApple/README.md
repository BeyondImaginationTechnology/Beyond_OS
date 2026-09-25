# Beyond French for iOS

Native SwiftUI beta for Beyond French 1.2.

## Learning and progress

- Generator-published Lesson of the Day
- Searchable multilingual dictionary
- Beginner, Intermediate, and Advanced Academy paths with free sequential lessons for the 1.2 beta
- Daily phrase practice
- Today, Academy, Translate, Dictionary, and More navigation
- Louis, Irie, Jazzy, and Pablo as selectable lesson guides

People can learn as guests. Signing in with Beyond ID merges guest completion into their account and syncs native Academy and daily practice progress between iPhones. Native Academy completion is currently separate from the web Academy catalog because the beginner lessons differ. The mobile token expires after one hour; signing in again resumes sync while local progress stays on the phone.

The 1.2 beta has no paid lesson unlock or in-app purchase flow. Module progression is based on completing the preceding lessons.

## Build

Generate the Xcode project with `xcodegen generate`, then open `BeyondFrench.xcodeproj`.
