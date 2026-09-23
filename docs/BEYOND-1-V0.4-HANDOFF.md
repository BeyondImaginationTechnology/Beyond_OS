# Beyond-1 v0.4 handoff

Paused: 2026-09-23

## Product decisions

- The app is called **Beyond-1**.
- The assistant/model is called **Jaguar**.
- Technical attribution can say: **Jaguar, powered by Llama**.
- Keep action-oriented modes such as Explain, Draw, and Video. Do not add separate visible modes for Daily Breath, Bible, or General Knowledge; those subjects should be recognized inside Explain.
- Add a simple **Test Jaguar** tool to the admin portal.
- Add two training scopes:
  - General Jaguar knowledge, shared across Beyond-1 and connected apps.
  - App-specific knowledge, such as Daily Breath.
- Training should feel like one simple text box. An administrator can enter a question or capability such as: “Is celery a fruit or vegetable?”
- Use retrieval/context at answer time for individual knowledge entries. Do not fine-tune the model for every new entry. GPT can optionally normalize or draft an answer, but Jaguar should use a reviewed knowledge library.
- Location should be a shared capability across web, iOS, and Android. Ask for permission only when needed, use approximate location where possible, and do not store location history.
- Nearby questions such as “What are the closest restaurants?” should use approved location plus a places provider. The admin Test Jaguar tool should support simulated locations.

## Current code state

- Local iOS app is renamed to Beyond-1 in `JaguarApple/`.
- iOS bundle identifier is `technology.co.beyondimagination.beyond1`.
- Apple Developer App ID was registered as `Beyond-1` with that bundle identifier.
- The old Jaguar developer identifier remains intentionally for compatibility.
- The old App Store Connect app was removed by the user.
- Codemagic is signed in and has an existing app connected to:
  `https://github.com/BeyondImaginationTechnology/Beyond_OS`
- `codemagic.yaml` was updated locally so the iOS workflows use:
  - workflow names `beyond1-ios-testflight` and `beyond1-ios`
  - bundle identifier `technology.co.beyondimagination.beyond1`
  - project `Beyond1.xcodeproj`
  - scheme `Beyond1`
- Those local changes have not been pushed and no TestFlight build has been started.

## Existing platform surfaces

- Web Jaguar chat: `ai/chat.php`
- Web Jaguar API: `ai/api/chat.php`
- Shared web mode catalog: `ai/includes/modes.php`
- Native iOS Jaguar app: `JaguarApple/`
- Android currently acts as a launcher into the Beyond OS web experience:
  `BeyondOSAndroid/app/src/main/java/technology/co/beyondimagination/os/MainActivity.java`
- Existing admin training page:
  `server/admin/daily-studio/jaguar-training.php`
- Existing training table is created in:
  `server/admin/daily-studio/bootstrap.php`
- Existing Daily Breath chat has guide-specific behavior in:
  `dailybreath/api/jaguar-chat.php` and `ai/api/chat.php`

## Important findings

- The current web/API experience already has Explain, Build, Draw, and Video entries. Explain is live; the others are still locked/planned.
- The current API already supports weather, geocoding, time, and place lookups using Open-Meteo.
- The current API does not yet provide restaurant/nearby-place search from user coordinates.
- The current admin training page requires a category, instruction, and ideal answer, and exports approved JSONL. It is currently scoped to the DailyBreath studio and does not yet provide a general Jaguar scope or live retrieval during chat.
- The current native iOS client sends `mode`, `language`, and the last 24 messages. It does not yet send location or a knowledge scope.
- The current Android app is a launcher, so Android location handling will need either a web-first permission flow or a future embedded/native Jaguar surface.

## Resume plan

1. Add a reviewed training knowledge layer with `scope` (`general` or app key), simple prompt/answer input, draft/approve states, and retrieval in `ai/api/chat.php`.
2. Add a general Jaguar admin portal section and a Test Jaguar panel with optional simulated location.
3. Add normalized `location` request data to the shared API and a nearby restaurants provider with privacy/rate limits.
4. Add browser geolocation consent to the web client.
5. Add iOS Core Location permission and pass approved coordinates to the API.
6. Update the Android wrapper/version and decide whether it should remain web-first or become an embedded Jaguar WebView with geolocation permission.
7. Update Beyond-1/Jaguar v0.4 labels and help text across clients.
8. Run PHP/API checks, web verification, Android Gradle checks, and the iOS/Codemagic build on macOS.
9. Push the corrected `codemagic.yaml` and source changes, then start the Beyond-1 TestFlight workflow.

## Safety/privacy reminders

- Never put API keys, private chats, or user coordinates into training data.
- Keep admin training reviewed before publication.
- Treat location as request-scoped data, not persistent memory.
- Keep general knowledge and app-specific knowledge clearly separated.
