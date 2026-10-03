# Beyond French for iOS

Native SwiftUI release for Beyond French 1.3.

## Learning and progress

- Generator-published Lesson of the Day
- Searchable multilingual dictionary
- Beginner, Intermediate, and Advanced Academy paths with free sequential lessons for 1.3
- Daily phrase practice
- Today, Academy, Translate, Dictionary, and More navigation
- Louis, Irie, Jazzy, and Pablo as selectable lesson guides

People can learn as guests. Signing in with Beyond ID merges guest completion into their account and syncs native Academy and daily practice progress between iPhones. Native Academy completion is currently separate from the web Academy catalog because the beginner lessons differ. The mobile token expires after one hour; signing in again resumes sync while local progress stays on the phone.

Version 1.3 has no paid lesson unlock or in-app purchase flow. Module progression is based on completing the preceding lessons.

## Build

Generate the Xcode project with `xcodegen generate`, then open `BeyondFrench.xcodeproj`.
