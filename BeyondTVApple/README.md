# Beyond TV for Apple platforms

Native SwiftUI and AVPlayer clients for iPhone, iPad, and Apple TV.

Current release: **1.1.0 (110)**. This is an internal iOS and tvOS build for channel operations and playback review.

## Open the project

Open `BeyondTV.xcodeproj` in Xcode. The project includes:

- `BeyondTV-iOS` for iPhone and iPad
- `BeyondTV-tvOS` for Apple TV
- `BeyondTVTests` for shared model and endpoint tests

Both application targets use the App ID:

`technology.co.beyondimagination.beyondtv`

The development team is configured as:

`FK9QM3VUNH`

## Run

1. Select the iOS or tvOS scheme.
2. Select a simulator or connected device.
3. Confirm automatic signing in **Signing & Capabilities**.
4. Build and run.

The app loads channel metadata from:

`https://beyondimagination.co.technology/beyond-tv/data/featured-channels.json`

It loads live schedules from the existing Beyond TV JSON APIs and uses AVPlayer for native MP4 or HLS playback. On iPhone and iPad, channels that publish an approved web player fall back to an in-app WKWebView. Apple TV displays those channels as iPhone/iPad channels because tvOS does not provide WKWebView; tvOS playback requires a direct HLS or MP4 source.

The tvOS Watch, Guide, and Browse screens omit channels and catalog titles that require a web player, so every item presented for tuning has a direct MP4 or HLS playback path.

## Distribution readiness

Before distribution:

- Build and sign on macOS with Xcode 26 or newer.
- Use a registered device and an Ad Hoc or development provisioning profile.
- Keep the signing certificate and provisioning profile outside the repository.
- Confirm documented streaming and distribution rights for every program exposed by a distributed build.

The manual Azure/TestFlight release path is documented in
`../docs/ci/BEYOND_TV_TESTFLIGHT_RELEASE.md`.

## 1.1.0 changes

- Opens every catalog card using its direct stream, Archive embed, candidate link, or source-search fallback.
- Switches from Browse to Watch when an approved title is selected.
- Calculates the guide's current block in `America/Vancouver`.
- Makes the light theme select the matching system appearance.
