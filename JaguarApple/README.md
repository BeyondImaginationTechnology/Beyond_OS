# Beyond-1 for iOS

Native SwiftUI app powered by the Beyond-1 v0.3 Preview service.

## v0.2

- A resilient Beyond ID sign-in experience with a native in-progress state,
  retry action, and safe troubleshooting detail rather than a blocking alert.
- A guided native start screen with clear Explain, Plan, and Learn entry points.
- Private diagnostic logging for failed token exchanges, including their HTTP
  status, without exposing server responses or account data in the UI.

## First release scope

- Beyond ID sign-in with ASWebAuthenticationSession, PKCE, and Keychain storage
- Explain mode chat through the existing Jaguar API
- English, French, and Spanish requests
- Local conversation history and new conversation controls
- Visible thinking stage and elapsed time
- Mobile transcript scrolling with a jump-to-latest control
- Rate-limit, expired-session, network, and server error states
- Reviewer demo mode with local responses when Beyond ID credentials are unavailable
- iPhone, iPad, and Mac Catalyst layouts

## Generate and run

Install XcodeGen on macOS, then:

```sh
cd JaguarApple
xcodegen generate
open Beyond1.xcodeproj
```

Choose the **Beyond1** scheme and an iOS 17 or newer simulator. The app requires a live Beyond ID account for chat. Tokens expire after one hour; an expired token returns the user to sign-in.

The checked-in privacy manifest declares the Beyond ID user identifier and chat prompts used for app functionality. App Store privacy answers must match the deployed service before distribution.

For App Review, tap **Try the reviewer demo** on the welcome screen. This opens the full conversation interface without an account and uses clearly labeled local responses; normal users can still use **Continue with Beyond ID** for live Jaguar responses.
