# Beyond ID first-party API

Beyond ID provides a PKCE-protected authorization-code flow for native Beyond
apps. It is a first-party API foundation, not a public third-party OAuth or
OpenID Connect provider. Public clients do not receive or embed client secrets.

## Discovery

`GET /beyond-id/api/discovery.php` returns the issuer, endpoints, registered
public clients, callback schemes, and scopes. This response contains no secrets.

Client registrations live in `beyond-id/config/api-clients.php`. Every native
app must have a unique audience/client ID and callback scheme. Add a client only
after its callback ownership and production bundle/package identity have been
reviewed.

## Native authorization flow

1. Generate a cryptographically random PKCE verifier containing 43–128 valid
   characters.
2. Compute `BASE64URL(SHA256(verifier))` as the challenge.
3. Open the Beyond ID login page with a safe return path:

   `/beyond-id/auth/login.php?return=/beyond-id/auth/mobile-complete.php?scheme=CLIENT_SCHEME%26code_challenge=CHALLENGE`

   A social-login-only experience may start at `auth/oauth-start.php` with the
   same encoded `return` parameter.
4. Receive the one-minute authorization code at `CLIENT_SCHEME://auth`.
5. Exchange it once with `POST /beyond-id/api/mobile-token.php` and JSON:

   `{"code":"...","code_verifier":"..."}`
   The response includes `token_type`, `expires_in`, `audience`, and the granted
   space-delimited `scope` alongside the access token.
6. Store the returned Bearer token in the platform keychain/secure credential
   store. Never place it in URLs, analytics, logs, preferences, or source code.
7. Call APIs with `Authorization: Bearer TOKEN`. Resource endpoints must verify
   both the fixed audience expected by that app and the required scope.
8. Revoke the token with `POST /beyond-id/api/mobile-token-revoke.php` during
   sign-out. Access tokens expire after one hour even if not explicitly revoked.

## Current scopes

- `profile:read`: basic Beyond ID profile access.
- `email:read`: email address access through the user-info endpoint.
- `wallet:read`: read-only bit$ wallet summary.
- `progress:write`: app progress or cloud-save writes.
- `watchlist:write`: Beyond TV watchlist writes.
- `streaks:write`: DailyBreath streak writes.

## Client rollout status

Beyond Music for Apple and French Quest for Apple already implement this PKCE
exchange. The Beyond TV and DailyBreath registrations reserve their first-party
audiences and callback schemes, but their native clients still need the PKCE,
secure-token-storage, authenticated API, and sign-out revocation integration
before those registrations should be treated as launched.

Register Android clients with their own client IDs and reviewed redirect
ownership; do not reuse an `-ios` audience across platforms.

The user-info endpoint omits email, role, and wallet information unless the
calling context is allowed to receive it. App-specific write endpoints must use
`beyond_mobile_verify_token()` with a fixed audience and then call
`beyond_mobile_require_scope()`.

## Operational requirements

- Configure a random `security.jwt_secret` of at least 32 bytes in protected
  `live.php`; rotate it as a planned global sign-out.
- Set `security.api_issuer` to the canonical HTTPS Beyond ID URL.
- Apply all Beyond ID database migrations before enabling clients.
- Keep HTTPS and HSTS enabled and preserve the Authorization header through the
  Apache/FastCGI boundary.
- Monitor token-exchange throttles and authentication failures without logging
  authorization codes, access tokens, secrets, or full request bodies.
- Treat Connected Apps revocation as immediate: it revokes active tokens and
  blocks later use by that app.

## Not yet provided

Refresh tokens, third-party developer registration, dynamic redirect URIs,
client secrets, consent-screen scope selection, asymmetric signing/JWKS, and
OpenID Connect ID tokens are intentionally out of scope. Add those through a
standards-based authorization server before opening Beyond ID to external
developers.

The JSON `/api/register.php` route remains a compatibility endpoint for the
current native client. New apps should use the hosted Beyond ID registration
experience so terms acceptance, bot protection, and account-recovery messaging
remain centralized.
