# Beyond-1 for Android

Native Android companion for Jaguar v0.5.1.

- Secure Beyond ID sign-in uses PKCE and Android Keystore-backed token storage.
- Explain chat sends authenticated requests to Jaguar and keeps the current
  conversation on the device.
- Reviewer demo mode runs locally without sending prompts to a server.
- English, French, and Spanish are available from the language control.

Open `JaguarAndroid` in Android Studio, allow Gradle sync, then run the `app`
configuration on Android 8.0 or newer. The production Beyond ID and Jaguar API
deployment must include the `jaguar-android` client registration.
