# Llama Jaguar for iOS

Native SwiftUI client for the Jaguar v0.3 Preview service.

## First release scope

- Beyond ID sign-in with ASWebAuthenticationSession, PKCE, and Keychain storage
- Explain mode chat through the existing Jaguar API
- English, French, and Spanish requests
- Local conversation history and new conversation controls
- Visible thinking stage and elapsed time
- Mobile transcript scrolling with a jump-to-latest control
- Rate-limit, expired-session, network, and server error states
- iPhone, iPad, and Mac Catalyst layouts

## Generate and run

Install XcodeGen on macOS, then:

```sh
cd JaguarApple
xcodegen generate
open Jaguar.xcodeproj
```

Choose the **Jaguar** scheme and an iOS 17 or newer simulator. The app requires a live Beyond ID account for chat. Tokens expire after one hour; an expired token returns the user to sign-in.

The checked-in privacy manifest declares the Beyond ID user identifier and chat prompts used for app functionality. App Store privacy answers must match the deployed service before distribution.
