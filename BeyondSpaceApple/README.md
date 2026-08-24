# Beyond Space for iOS

Native SwiftUI starter app for the Beyond Space daily space fact and horoscope experience.

## Open and run

1. On a Mac with Xcode 26 or newer, open `BeyondSpace.xcodeproj`.
2. Select the shared `BeyondSpace` scheme and an iPhone simulator.
3. Choose your Apple development team if Xcode asks, then Run.

The project targets iOS 17 and includes no third-party dependencies. `project.yml` is included so the project can also be regenerated with XcodeGen.

## Accessibility baseline

- Dynamic Type with reflowing content and no fixed-height reading cards
- VoiceOver labels, headings, grouped content, and link hints
- minimum 44-point interactive targets
- Reduce Motion and Reduce Transparency support
- strong dark-theme contrast and text labels in addition to color
- horoscope mood pill remains in normal layout flow, preventing copy overlap

The app icon and gold-on-midnight palette use the existing Beyond Space brand artwork from the web/Instagram project. Before App Store submission, run Xcode tests, Accessibility Inspector, VoiceOver, and the largest Accessibility text sizes on physical iPhone and iPad hardware.
