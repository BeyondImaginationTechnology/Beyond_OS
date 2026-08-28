# Beyond TV for Apple platforms

Native SwiftUI and AVPlayer clients for iPhone, iPad, and Apple TV.

Current release: **1.1.0 (110)**. The iOS build is intended for private development or Ad Hoc distribution. Beyond TV on the web remains free, the private iOS experience advertises a 365-day no-card trial, and Android is coming soon.

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

## Distribution readiness

Before distribution:

- Build and sign on macOS with Xcode 26 or newer.
- Use a registered device and an Ad Hoc or development provisioning profile.
- Keep the signing certificate and provisioning profile outside the repository.
- Confirm documented streaming and distribution rights for every program exposed by a distributed build.
- Configure a production membership checkout only after the entitlement backend is available; 1.1.0 advertises the approved offer but does not collect payment.

## 1.1.0 changes

- Moves Beyond ID mobile tokens to Keychain and sends them with a bearer header.
- Prevents watchlist, candidate, collection, and pending-review records from resolving playback.
- Switches from Browse to Watch when an approved title is selected.
- Calculates the guide's current block in `America/Vancouver`.
- Makes the light theme select the matching system appearance.
- Adds the privacy manifest and Beyond Supporter membership presentation.
