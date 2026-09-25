# Beyond ID mobile API v1

Beyond ID remains a first-party integration API for Beyond apps. Its discovery
document and signed access tokens are not an OpenID Connect implementation.
The v1 contract is in `api/v1/openapi.yaml`; existing v0.3 routes remain
available while native apps migrate.

## Session model

- Native sign-in uses the system browser or platform authentication session,
  PKCE S256, a one-time authorization code, and a fresh verifier/challenge per
  attempt. Existing app callback schemes remain supported.
- New 0.4 token exchanges send `token_version: "0.4"`, a random per-install
  `device_id`, and a display-only `device_name`. They receive a 15-minute
  access token and a rotating refresh token. Only the SHA-256 hash of each
  refresh token is stored. Refresh tokens roll every use, last up to 30 days
  between uses, and are bounded by a 180-day device-session lifetime.
- Reuse of a consumed refresh token revokes its complete device family.
  Security → Mobile app devices signs out one device. Connected apps can
  revoke an app, or adjust its approved permissions; either action revokes
  active mobile sessions for that app.
- Exchanges that omit `token_version` retain the v0.3 one-hour access-token
  lifetime for older shipped clients. Move each app to 0.4 refresh handling
  before removing that compatibility period.

## v1 contract rules

- Stable subject IDs are strings; returned timestamps use RFC 3339 UTC.
- Endpoints check the audience and required scopes. `401` is an invalid or
  expired session; `403` is valid identity without required permission; `409`
  is a stale revision or reused idempotency key with different content; `429`
  includes `Retry-After`.
- Errors include a stable code and request ID. Retryable writes use
  `Idempotency-Key`. Offline sync updates use `If-Match` and return `409` when
  the stored revision changed. Future collection endpoints use opaque cursors
  and a bounded page size.
- Authenticated v1 endpoints allow 120 requests per user/IP in five minutes.
  French progress has additional limits of 120 reads and 30 writes per five
  minutes. A `429` includes `Retry-After`.
- `GET /api/v1/me.php` returns only the minimal profile contract. Email is
  omitted unless `email:read` is granted.
- `GET` and `PUT /api/v1/french-progress.php` form the first sync pilot. The
  snapshot merge preserves completed lesson IDs and the highest practice
  count; a revision mismatch asks the client to fetch, merge, and retry.

## Integration order

1. Identity profile: implemented at `/api/v1/me.php`.
2. App permissions and device controls: available in Beyond ID Connected apps
   and Security pages. App consent is shown during native authorization.
3. Cloud sync: French progress pilot is implemented at
   `/api/v1/french-progress.php`; v0.3 sync remains available to older clients.
4. Entitlements: pending server-side Apple and Google purchase verification.
   Client-reported purchases are not proof of ownership.
5. Push registration: not enabled. Add it only with a dedicated scope, app
   ownership validation, and provider token lifecycle handling. Account
   deletion requests use the `account:delete` scope and are restricted to
   registered app audiences that expose the feature.

## Verified HTTPS callbacks

Custom app schemes remain the callback fallback during migration. The
`/.well-known/apple-app-site-association` and
`/.well-known/assetlinks.json` files are not published yet: they require the
Apple Team ID and each iOS bundle ID, plus the Android package ID and SHA-256
fingerprint of the actual release signing certificate. Do not use debug or
upload-certificate fingerprints as a substitute for the Play App Signing
certificate. Once those exact production values are available, publish and
verify each file over HTTPS before registering Universal Links/App Links in
the signed apps.

## Deployment order

1. Take a current database backup.
2. Apply the matching MySQL or SQLite migration in
   `database/migrations/20260925_01_mobile_refresh_sessions_*` before deploying
   the new PHP token code.
3. Deploy the Beyond ID PHP changes and `api/v1/openapi.yaml`.
4. Release native app builds with refresh-token storage in Keychain / Android
   Keystore and `token_version: "0.4"`.
5. Confirm refresh, replay revocation, single-device sign-out, app disconnect,
   and sync conflict behavior against the deployed database before migrating
   remaining clients.
