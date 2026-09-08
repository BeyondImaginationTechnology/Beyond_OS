# Couple Mode security review

## Resolved in 1.1

- Replaced the iOS process-random `hashValue` invite with a persisted random ID.
- Replaced predictable web IDs derived from partner names with Web Crypto IDs.
- Uses 64-bit random server invite codes with a uniqueness constraint and retry.
- Uses 256-bit random member bearer tokens.
- Stores hashes of invite codes and bearer tokens, never the secrets themselves.
- Limits spaces to two member roles and serializes joins with `BEGIN IMMEDIATE`.
- Returns a member's own picks plus mutual matches; partner-only picks are never returned.
- Uses parameterized SQL, strict decision values, expiry, foreign keys, and cascade cleanup.

## Deployment gates

- Serve the API only over TLS.
- Keep the built-in 20-attempt/10-minute join limit enabled and add an edge
  limit as a second layer when the hosting control panel supports it.
- Set `BEYOND_BABY_NAMES_DB` to a backed-up, non-public writable path.
- Add a scheduled job to delete expired spaces.
- Store mobile bearer tokens in Keychain on iOS and Keystore-backed storage on Android
  when the clients are connected to the API.

The 1.1 web client uses the same-origin database API and keeps its member token
in session storage. Mobile sync should not be enabled until Keychain/Keystore
token storage and the production API base URL are configured.
