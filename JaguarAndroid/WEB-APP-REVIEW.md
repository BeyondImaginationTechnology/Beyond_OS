# Jaguar web and Android review — v0.5.1

## Shared behavior

- The web app and Android app use Explain (`core`) for normal chat, with
  English, French, and Spanish requests.
- Native sign-in sends a mobile Bearer token. The Android client identifies
  itself with `X-Jaguar-Client: android`; the API validates it only against the
  registered `jaguar-android` Beyond ID audience.
- Code Thinking remains admin-only and is absent from the Android client.
- Reviewer demo responses remain local to the device and do not call Jaguar.

## Deliberate scope difference

The web app exposes signed-in GPU Draw when its worker, wallet, and commercial
model license are ready. Android v0.5.1 ships Explain and reviewer demo only.
It does not claim to create images or debit BIT$ until a native image result,
wallet receipt, and seven-day recovery flow are designed and tested.

## Deployment checks

1. Deploy the `ai/` and `beyond-id/` changes together so the API recognizes
   `jaguar-android` before Android users sign in.
2. For the Draw recovery release, include the SQLite migrations
   `20260929_01_jaguar_draw_holds_sqlite.sql` and
   `20261003_01_jaguar_draw_images_sqlite.sql`.
3. Confirm `/ai/chat.php` is serving v0.5.1 before releasing Android. The last
   public check before this Android project was added still returned the older
   web build.
