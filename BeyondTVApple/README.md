# Beyond TV for Apple platforms

Native SwiftUI and AVPlayer clients for iPhone, iPad, and Apple TV.

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

## App Store readiness

Before distribution:

- Review the generated iOS and tvOS icons in App Store Connect against final brand artwork.
- Add the tvOS privacy policy text in App Store Connect.
- Configure Sign in with Apple only when Beyond ID authentication is implemented in the app.
- Add Associated Domains only after the `apple-app-site-association` file is deployed.
- Confirm documented streaming and distribution rights for every program exposed to App Review.
